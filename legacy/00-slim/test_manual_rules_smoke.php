<?php
/**
 * Quick smoke test for fingerprint + rule engine (no DB).
 * php test_manual_rules_smoke.php
 */
require_once __DIR__ . '/PropFingerprint.php';
require_once __DIR__ . '/PropManualRuleEngine.php';

$props = array(59, 40, 33, 33, 33, 28, 28, 26, 24, 24, 21, 19);
$fp = PropFingerprint::buildFromProps($props);
$fp['venue_bucket'] = 'Sx';

echo "prop: " . $fp['prop_string'] . "\n";
echo "band2 c2x=" . $fp['bands']['2']['c_pair'] . " groups:\n";
foreach ($fp['bands']['2']['groups'] as $g) {
    echo "  {$g['label']} v{$g['value']} {$g['size']} ord{$g['ord']} tokens=" . json_encode($g['tokens']) . "\n";
}
echo "band3 groups:\n";
foreach ($fp['bands']['3']['groups'] as $g) {
    echo "  {$g['label']} v{$g['value']} {$g['size']} tokens=" . json_encode($g['tokens']) . "\n";
}

$rule = array(
    'code' => 'TEST-R001',
    'enabled' => 1,
    'mark_weight' => 1,
    'venue_scope' => 'Sx',
    'condition_json' => array(
        'all' => array(
            array('venue_bucket' => 'Sx'),
            array('op' => 'only_label', 'label' => '33x'),
        ),
    ),
    'pick_json' => array(
        'marks' => array(
            array('ref' => 'pos332', 'of' => array('band' => '3', 'label' => '33x')),
        ),
    ),
);

// This string also has 2x groups so only_label=33x should FAIL
$r = PropManualRuleEngine::applyRule($fp, $rule);
echo "only_33x match (expect false): " . ($r['matched'] ? 'true' : 'false') . "\n";

$rule2 = $rule;
$rule2['condition_json'] = array(
    'all' => array(
        array('venue_bucket' => 'Sx'),
        array('op' => 'has_label', 'band' => '3', 'label' => '33x'),
        array('op' => 'c2x', 'band' => '2', 'eq' => 2),
    ),
);
$rule2['pick_json'] = array(
    'marks' => array(
        array('ref' => 'pos21', 'of' => array('band' => '2', 'label' => '2x', 'size' => 'big')),
        array('ref' => 'pos22', 'of' => array('band' => '2', 'label' => '2x', 'size' => 'small')),
        array('ref' => 'pos332', 'of' => array('band' => '3', 'label' => '33x')),
    ),
);
$r2 = PropManualRuleEngine::applyRule($fp, $rule2);
echo "c2x=2 + 33x match: " . ($r2['matched'] ? 'true' : 'false') . " picks=" . json_encode($r2['picks']) . "\n";

$eval = PropManualRuleEngine::evaluateString($props, array($rule2), 'Sx');
echo "eval hit_rules=" . $eval['hit_rule_count'] . " top_picks=" . json_encode($eval['top_picks']) . "\n";
echo "OK\n";
