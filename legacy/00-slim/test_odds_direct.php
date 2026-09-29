<?php
// test_working_query.php - 带变量的查询测试

require_once 'HKJC_graphql_client.php';

$client = new GraphQLClient();

// 使用与原来成功脚本相同的查询
$query = '
query racing($date: String, $venueCode: String) {
  raceMeetings(date: $date, venueCode: $venueCode) {
    date
    venueCode
    totalNumberOfRace
    races {
      no
      runners {
        no
        finalPosition
        winOdds
        horse {
          code
        }
      }
    }
  }
}';

$variables = [
    'date' => '2026-05-09',
    'venueCode' => 'ST'
];

echo "=== 测试带变量的查询 ===\n";
try {
    $result = $client->execute($query, $variables);
    echo "✅ 成功！\n";
    if (!empty($result['data']['raceMeetings'][0]['races'][0]['runners'])) {
        echo "第1场有 " . count($result['data']['raceMeetings'][0]['races'][0]['runners']) . " 匹马\n";
    }
} catch (Exception $e) {
    echo "❌ 失败: " . $e->getMessage() . "\n";
}

// 测试赔率查询
$query2 = '
query odds($date: String, $venueCode: String) {
  raceMeetings(date: $date, venueCode: $venueCode) {
    pmPools(oddsTypes: [WIN, PLA]) {
      oddsType
      oddsNodes {
        combString
        oddsValue
        hotFavourite
      }
    }
  }
}';

echo "\n=== 测试赔率查询 ===\n";
try {
    $result2 = $client->execute($query2, $variables);
    echo "✅ 成功！\n";
    if (!empty($result2['data']['raceMeetings'][0]['pmPools'])) {
        echo "赔率数据存在\n";
    } else {
        echo "赔率数据为空（可能尚未发布）\n";
    }
} catch (Exception $e) {
    echo "❌ 失败: " . $e->getMessage() . "\n";
}
?>