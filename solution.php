<?php 
class Bouquet {
    public 
        $name, 
        $size, 
        $total_quantity,
        $required_flowers, // { name: quantity }
        $flowers = []; // { name: { name: string, quantity: int } }

    function __construct (
        string $name, 
        $size, 
        int $total_quantity, 
        array $required_flowers
    ) {
        $this->name = $name; 
        $this->size = $size;
        $this->required_flowers = array_reduce(
            $required_flowers, 
            function ($xs, $x) { 
                $xs[$x['name']] = $x['quantity'];
                return $xs;
            }, 
            []);
        $this->total_quantity = $total_quantity; 
    }

    function is_ready () : bool { 
        foreach ($this->required_flowers as $flower_name => $quantity) {
            if (
                !key_exists($flower_name, $this->flowers)
                or
                $quantity > $this->flowers[$flower_name]['quantity']
            ) return false; 
        } 
        return true;
    }

    function is_flower_needed (string $flower_name) : bool {
        return (
            key_exists($flower_name, $this->required_flowers)
            and 
            ($this->flowers[$flower_name]['quantity'] ?? 0) < $this->required_flowers[$flower_name]);
    }

    function free_space () : int {
        $reserved = array_sum($this->required_flowers);

        $used = array_sum(array_map(
            function ($f) {
                if (!key_exists($f['name'], $this->required_flowers)) {
                    return $f['quantity']; 
                } 
                if ($f['quantity'] > $this->required_flowers[$f['name']]) {
                    return $f['quantity'] - $this->required_flowers[$f['name']];
                }

                return 0;
            },
            $this->flowers
        ));

        return $this->total_quantity - $reserved - $used;
    }

    function has_free_space () : bool {
        return $this->free_space() > 0; 
    }

    function add_flower (string $flower_name) { 
        if (!key_exists($flower_name, $this->flowers)) {
            $this->flowers[$flower_name] = ['name' => $flower_name, 'quantity' => 0];
        }
        $this->flowers[$flower_name]['quantity']++;
    }

    function to_string () : string { 
        ksort($this->flowers);
        return 
            $this->name.
            $this->size.
            join('', array_map(function ($f) { return $f['quantity'].$f['name']; }, $this->flowers));
    }

}
/**
 * parse input bouquet specifications
 * @return array 
 * format: { bouquet_name : Bouquet }
 */
function read_bouquets () : array {
    $result = []; 
    while ($spec = fgets(STDIN) and $spec != "\n") {
        preg_match_all("~(?<name>[A-Z])(?<size>[A-Z])(?<flowers>(\d+[a-z])+)(?<total_quantity>\d+)~", $spec, $bouquet);
        preg_match_all("~(\d+)([a-z])~", $bouquet['flowers'][0], $flowers); 
        
        $result[$bouquet['name'][0].$bouquet['size'][0]] = new Bouquet (
            $bouquet['name'][0],
            $bouquet['size'][0],
            (int) $bouquet['total_quantity'][0],
            array_map(
                function ($quantity, $name) { 
                    return [
                        'name' => $name, 
                        'quantity' => (int) $quantity,
                    ]; 
                },
                $flowers[1],
                $flowers[2]
            )
        );
    }
    return $result;
}

/**
 * we need index to reduce search for available bouquet complexity from O(kn) to O(1)
 * format: { flower : [ bouquet_name_size ] }
 */
function build_index (array $bouquets) : array {
    $index = [];
    foreach ($bouquets as $b) {
        foreach (array_keys($b->required_flowers) as $flower_name) {
            if (!key_exists($flower_name.$b->size, $index)) {
                $index[$flower_name.$b->size] = [];
            }
            $index[$flower_name.$b->size][] = $b->name.$b->size;
        }
    }
    return $index; 
}

$bouquets = read_bouquets(); 
$index = build_index($bouquets);

$flowers_in_storage = 0; 
while ($line = fgets(STDIN)) {
    if ($flowers_in_storage == 256) {
        echo "No more available space in storage";
        exit(1);
    }
    preg_match("~([a-z])([A-Z])~", $line, $matches);
    list($flower, $flower_name, $flower_size) = $matches;
    
    if (key_exists($flower, $index)) {
        $bouquet_name_size = $index[$flower][0];

        if ($bouquets[$bouquet_name_size]->is_flower_needed($flower_name)) {
            $bouquets[$bouquet_name_size]->add_flower($flower_name); 

            if ($bouquets[$bouquet_name_size]->is_ready()) {
                echo $bouquets[$bouquet_name_size]->to_string()."\n"; 
            }
        } else {
            array_shift($index[$flower]);
        }

        if (empty($index[$flower])) unset($index[$flower]);

    } else {
        foreach ($bouquets as $bouquet_name_size => $b) {
            if ($b->size == $flower_size and $b->has_free_space()) {
                $bouquets[$bouquet_name_size]->add_flower($flower_name); 
                break; 
            }
        }

    }
    $flowers_in_storage++;
}