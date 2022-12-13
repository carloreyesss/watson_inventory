<?php
require_once realpath(__DIR__ . '/vendor/autoload.php');
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

/**
 * Read and get the list of SKUs on the CSV file
 * 
 * @return null
 */
$skus = [];
if(!file_exists($argv[1])){
  print_r("File not exists");
  return false;
    
} else {
  $file = fopen($argv[1], 'r');
  while (($line = fgetcsv($file)) !== FALSE) {
    $skus[] = $line[0];
  }

  array_shift($skus);
  implode(',',$skus);

  $postData = [
    "api_key" => $_ENV["API_KEY"],
    "email" => $_ENV["EMAIL"],
    "signature" => $_ENV["SIGNATURE"],
    "product_skus" => $skus
  ];

  fclose($file);

  getDataAndConvertDownloadCsv();
}

/**
 * Get the list of data, convert to CSV and download
 * 
 * @return null
 */
 function getDataAndConvertDownloadCsv()
 {
  $curl = curl_init();

  curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://ewms.anchanto.com/fetch_stock',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'GET',
    CURLOPT_POSTFIELDS => json_encode($postData),
    CURLOPT_HTTPHEADER => array(
      'Content-Type: application/json',
      'Cookie: _order_management_session=BAh7BkkiD3Nlc3Npb25faWQGOgZFVEkiJWRmZTlmZTVmMDA2MWZjYzI5N2RlZTJjODFkYWZiNmU2BjsAVA%3D%3D--70903fae784a22a30299427d9b6c8f2dcf3a88df; locale=en'
    ),
  ));

  $response = curl_exec($curl);

  curl_close($curl);

  $responseJSON = json_decode($response, true);
  $products = json_encode($responseJSON['products']);

  $productArr = [
    [
      'Variant Command' => 'Variant Command',
      'Variant SKU' => 'Variant SKU',
      'Inventory Available: Fluent Total' => 'Inventory Available: Fluent Total',
      'Inventory Available: SM ESTORE Cubao' => 'Inventory Available: SM ESTORE Cubao',
    ]
  ];

  foreach(json_decode($products) as $product){
    if($product->item_type != 'not_in_fba'){
      $productArr[] = array(
        'variant_command' => 'UPDATE',
        'variant_sku' => $product->sku,
        'inventory_available_fluent_total' => $product->quantity,
        'inventory_available_sm_estore_cubao' => $product->quantity
      );
    }
  }
  $jsonData = json_encode($productArr);

  // Convert to CSV and download
  $jsonDecoded = json_decode($jsonData, true);
  $csv = date('Ymd') . '-' . $argv[1];
    
  $file_pointer = fopen($csv, 'w');
    
  foreach($jsonDecoded as $i){
    fputcsv($file_pointer, $i);
  }

  fclose($file_pointer);

 }
