<?PHP
function doOperationRelation (&$saddr, $id) {
    $results = [
        'display' => 'relations.tpl',
        'search_results' => [],
        'result_count' => 0,
        'relation_name' => [
            'worker' => 'travaille',
            'branch' => 'succursale'
        ]
    ];
    $dn = saddr_urlDecrypt($saddr, $id);
    if ($dn) {
        $results['search_results'] = saddr_listRelation($saddr, $dn);
        if (!empty($results['search_results']['__parent'])) {
            $results['__parent'] = $results['search_results']['__parent'];
        }
        unset ($results['search_results']['__parent']);
        $results['result_count'] = count($results['search_results']);
    }

    return $results;
}
?>