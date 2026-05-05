<?php
$_SERVER['SERVER_NAME'] = $_SERVER['SERVER_NAME'] ?? 'localhost';
$_SERVER['HTTPS'] = $_SERVER['HTTPS'] ?? 'off';
require __DIR__ . '/../../config/db.php';
$db = new Database();
$pdo = $db->getConnection();
if (!$pdo) { echo "DB connection failed\n"; exit(1); }
$r=$pdo->query("SELECT id,title,main_image FROM products WHERE title LIKE '%1000MG HIGH STRENGTH VITAMIN C%' ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if(!$r){ echo "No product found\n"; exit; }
$pid=(int)$r['id'];
echo "Product: {$r['id']} | {$r['title']} | main_image={$r['main_image']}\n";
$st=$pdo->prepare("SELECT id, product_id, image_path FROM product_images WHERE product_id=:pid ORDER BY id ASC");
$st->execute([':pid'=>$pid]);
$rows=$st->fetchAll(PDO::FETCH_ASSOC);
echo "Gallery rows for product {$pid}: ".count($rows)."\n";
foreach($rows as $row){
  $v = $row['image_path'];
  $show = ($v===null)?'NULL':('['.$v.'] len='.strlen((string)$v));
  echo "- id={$row['id']} path={$show}\n";
}
?>
