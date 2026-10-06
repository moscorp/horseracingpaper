<?php
/** Debug Sx-R004 rule on two races */
require_once __DIR__ . '/PropFingerprint.php';
require_once __DIR__ . '/PropManualRuleEngine.php';

$rule = array(
    'code' => 'Sx-R004',
    'enabled' => 1,
    'venue_scope' => 'Sx',
    'mark_weight' => 1,
    'condition_json' => array(
        'all' => array(
            array('venue_bucket' => 'Sx'),
            array('op' => 'not_top', 'ref' => 'w5'),
        ),
    ),
    'pick_json' => array(
        'marks' => array(
            array('ref' => 'w6', 'of' => array('size' => 'small', 'ord' => 1)),
            array('ref' => 'w2', 'of' => array('size' => 'big', 'ord' => 1)),
        ),
    ),
);

$cases = array(
    'S3_R7' => array(
        'props' => array(100,40,27,42,50,29,28,19,26,23,26,23,20,16),
        'fps'   => array(6,3,8,2,10,4,1,11,5,7,12,9,14,13), // placeholder - user only mentioned 29=4
    ),
    'S1_R5' => array(
        'props' => array(44,20,57,28,34,38,26,29,31,28,30,28,26,27,26),
        'fps'   => null,
    ),
);

// S3 - user said 29 is FP4; need actual fps - use null for unknown, mark 29's pos
$s3fps = array_fill(0, 14, null);
$s3fps[5] = 4; // 29 at pos 5
$cases['S3_R7']['fps'] = $s3fps;

// S1 - user said 28 is FP1; 28 at pos 3,9,11
$s1fps = array_fill(0, 15, null);
$s1fps[3] = 1; // first 28
$cases['S1_R5']['fps'] = $s1fps;

foreach ($cases as $name => $c) {
    $fp = PropFingerprint::buildFromProps($c['props'], $c['fps']);
    $fp['venue_bucket'] = 'Sx';
    $fp['racingdate'] = 'test';
    $fp['venue'] = 'S1';
    $fp['raceno'] = 1;

    echo "=== $name prop: " . implode(',', $c['props']) . " ===\n";
    echo "w5: " . json_encode($fp['bands']['5']['w']) . "\n";
    echo "w2: " . json_encode($fp['bands']['2']['w']) . "\n";
    echo "w6: " . json_encode($fp['bands']['6']['w']) . "\n";

    $cond = PropManualRuleEngine::matchCondition($fp, $rule['condition_json']);
    echo "condition match: " . ($cond ? 'YES' : 'NO') . "\n";

    $r = PropManualRuleEngine::applyRule($fp, $rule);
    echo "matched: " . ($r['matched'] ? 'YES' : 'NO') . " picks pos: " . json_encode($r['picks']) . "\n";
    foreach ($r['picks'] as $pos) {
        $v = $fp['prop_order'][$pos];
        $fpv = isset($fp['cells'][$pos]['fp']) ? $fp['cells'][$pos]['fp'] : '?';
        echo "  pick pos=$pos prop=$v fp=$fpv\n";
    }
    echo "\n";
}
