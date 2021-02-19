<?PHP
function echo_generic_csv($results)
{
    $mapping = [
        'module' => 'Type',
        'name' => 'Nom',
        'firstname' => 'Prénom',
        'lastname' => 'Nom de famille',
        'displayname' => 'Surnom',
        'title' => 'Titre',
        'home_mobile' => 'Mobile',
        'iban' => 'IBAN',
        'company' => 'Société',
        'work_phone' => 'Téléphone professionnel',
        'work_email' => 'Email professionnel',
        'work_fax' => 'Fax professionnel',
        'work_address' => 'Adresse professionnelle',
        'work_city' => 'Localité professionnelle',
        'work_npa' => 'Code postale professionnel',
        'work_state' => 'Canton/département professionnel',
        'work_country' => 'Pays professionnel',
        'home_telephone' => 'Téléphone privé',
        'url' => 'Site web',
        'home_address' => 'Adresse privée',
        'home_city' => 'Localité privée',
        'ĥome_npa' => 'Code postale privé',
        'home_state' => 'Canton/département privé',
        'home_country' => 'Pays privée',
        'description' => 'Détails',
        'tags' => 'Mots clés',
        'dn' => 'Identifiant interne'
    ];
    $first = true;
    foreach ($mapping as $field) {
        if (!$first) {
            echo ',';
        }
        echo '"' . $field . '"';
        $first = false;
    }
    echo "\n";
    foreach ($results as $v) {
        $first = true;
        foreach ($mapping as $k => $_) {
            switch($k) {
                case 'module':
                    if ($v[$k] === 'iroorganization') {
                        $v[$k] = ['Société'];
                    } else if($v[$k] === 'iroperson') {
                        $v[$k] = ['Personne'];
                    } else {
                        continue 3;
                    }
                    break;
                case 'dn':
                    $v[$k] = [$v[$k]];
                    break;
            }
            if (!$first) {
                echo ',';
            }
            $first = false;
            if (isset($v[$k])) {
                $val = [];
                foreach ($v[$k] as $_v) {
                    $val[] = str_replace('"', '\\"', trim($_v));
                }
                echo '"' . implode("\n", $val) . '"';
            } else {
                echo '""';
            }
        }
        echo "\n";
    }
}
?>