<?php

if ( !defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}


/**
 * Add shortcode to display export button
 */
add_shortcode( 'pq_operations_exporter_button', 'pq_operations_exporter_button_fct' );

function pq_operations_exporter_button_fct() {

  if ( current_user_can( 'pq_see_operations' ) ) {
    if ( isset( $_POST[ 'operations_export_form' ] ) && check_admin_referer( 'operations_export_clicked' ) ) {
        pq_export_operations_lists();
    }

    if ( isset( $_POST[ 'labels_export_form' ] ) && check_admin_referer( 'labels_export_clicked' ) ) {
        pq_export_labels();
    }

    $timezone = new DateTimeZone( get_option( 'timezone_string' ) );
    $today_date_obj = new DateTime( 'today', $timezone );
    $today_date = $today_date_obj->format( 'Y-m-d' );
    $orders_today = myfct_get_relevant_orders( $today_date );

    echo '<h4>Nombre de commandes aujourd\'hui: ' . count($orders_today) . '</h4>';

    $tomorrow_date_obj = new DateTime( 'tomorrow', $timezone );
    $tomorrow_date = $tomorrow_date_obj->format( 'Y-m-d' );
    $orders_tomorrow = myfct_get_relevant_orders( $tomorrow_date );
    echo '<h4>Nombre de commandes demain: ' . count($orders_tomorrow) . '</h4>';

    ?>
    <form action="" method="post">
      <?php wp_nonce_field('operations_export_clicked'); ?>
      
      <input type="hidden" value="true" name="operations_export_form" />
      <input type="submit" value="Exporter les listes">
    </form>

    </br>

    <form action="" method="post">
      <?php wp_nonce_field('labels_export_clicked'); ?>
      
      <input type="hidden" value="true" name="labels_export_form" />
      <input type="submit" value="Exporter les étiquettes">
    </form>
    <?php
  }
}


/**
 * Export all the lists for daily operations
 */
function pq_export_operations_lists() {
    require PQ_VENDOR_DIR . 'autoload.php';

    $timezone = new DateTimeZone( get_option( 'timezone_string' ) );
    $default_date_obj = new DateTime( 'today', $timezone );
    $default_date = $default_date_obj->format( 'Y-m-d' );
    $orders = myfct_get_relevant_orders( $default_date );

    $product_rows = pq_get_product_rows($orders);

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

    pq_print_sheets($spreadsheet, $product_rows);

    pq_style_sheets($spreadsheet);

    pq_export_excel($spreadsheet);
}


/**
 * Print all the worksheets
 */
function pq_print_sheets($spreadsheet, $product_rows) {
  //Print Epicerie
  $epicerie_sheet = $spreadsheet->getActiveSheet();
  $epicerie_sheet->setTitle('Epicerie');

  $to_print = array('supplier', '_short_name', 'total_quantity');

  $epicerie_sheet->setCellValue('A1', 'Marchand');
  $epicerie_sheet->setCellValue('B1', 'Nom court');
  $epicerie_sheet->setCellValue('C1', 'Conso');

  pq_print_on_sheet( $epicerie_sheet, $product_rows, 1, 2, $to_print );

  //Print Frais
  $frais_sheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'Frais');
  $spreadsheet->addSheet($frais_sheet, 0);

  $to_print = array('supplier', '_short_name', 'total_quantity', 'weight');

  $frais_sheet->setCellValue('A1', 'Marchand');
  $frais_sheet->setCellValue('B1', 'Nom court');
  $frais_sheet->setCellValue('C1', 'Conso');
  $frais_sheet->setCellValue('D1', 'Poids');

  pq_print_on_sheet( $frais_sheet, $product_rows, 20, 29, $to_print );

  //Sort from short_name
  $columns = array_column($product_rows, '_short_name');
  array_multisort($columns, SORT_ASC, SORT_STRING, $product_rows);

  //Print Centrale
  $centrale_sheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'Centrale');
  $spreadsheet->addSheet($centrale_sheet, 0);

  $to_print = array('_short_name', 'total_quantity');

  $centrale_sheet->setCellValue('A1', 'Nom court');
  $centrale_sheet->setCellValue('B1', 'Conso');

  pq_print_on_sheet( $centrale_sheet, $product_rows, 10, 19, $to_print );

  //Print Centrale en inventaire à l'exterieur
  $centrale_stock_sheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'Centrale en stock');
  $spreadsheet->addSheet($centrale_stock_sheet, 0);

  $to_print = array('_short_name', 'total_quantity');
  $products_to_print = 'centrale-exterieur';

  $centrale_stock_sheet->setCellValue('A1', 'Nom court');
  $centrale_stock_sheet->setCellValue('B1', 'Conso');

  pq_print_on_sheet( $centrale_stock_sheet, $product_rows, 1, 999, $to_print, $products_to_print );

  //Print Pesée
  $peser_sheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'Peser');
  $spreadsheet->addSheet($peser_sheet, 0);

  $to_print = array('_short_name', 'total_quantity', 'weight');
  $products_to_print = 'a-peser';

  $peser_sheet->setCellValue('A1', 'Nom court');
  $peser_sheet->setCellValue('B1', 'Conso');
  $peser_sheet->setCellValue('C1', 'Poids');

  pq_print_on_sheet( $peser_sheet, $product_rows, 1, 999, $to_print, $products_to_print );
}


/**
 * Print content onto a worksheet
 */
function pq_print_on_sheet( $sheet, $product_rows, $low_priority, $high_priority, $to_print, $products_to_print = '' ) {

  $row = 2;
  foreach ( $product_rows as $product_id => $product_row ) {
    $column = 1;
    $packing_priority = $product_row['_packing_priority'];
    $inventory_type = $product_row['pq_inventory_type'];

    if ( $packing_priority >= $low_priority && $packing_priority <= $high_priority && 
      ((empty($inventory_type) && empty($products_to_print)) || in_array($products_to_print, $inventory_type)) ) {
      foreach ( $product_row as $name => $cell_value ) {
        if ( in_array($name, $to_print) ) {
          $sheet->setCellValueByColumnAndRow($column, $row, $cell_value);
          $column++;
        }
      }
      $row++;
    }
  }
}


/**
 * Style all the worksheets
 */
function pq_style_sheets($spreadsheet) {
  for ( $i = 0; $i <= $spreadsheet->getSheetCount() - 1; $i++ ) {
    $sheet = $spreadsheet->getSheet($i);
    $last_column_string = $sheet->getHighestDataColumn();
    $columns_count =  \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($last_column_string);
    $rows_count = $sheet->getHighestDataRow();

    //Set column width
    for ( $j = 1; $j <= $columns_count; $j++ ) {
      $sheet->getColumnDimensionByColumn($j)->setAutoSize(true);
    }

    //Format 1st row
    $styleArray = [
      'font' => [
        'bold' => true,
        'color' => [
          'argb' => 'FFFFFF',
        ],
      ],
      'fill' => [
        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
        'startColor' => [
          'argb' => '4472C4',
        ],
      ],
    ];
    $sheet->getStyle('A1:' . $last_column_string . '1')->applyFromArray($styleArray);

    //Format all other rows
    for ( $j = 2; $j <= $rows_count; $j = $j + 2 ) {
      $styleArray = [
        'fill' => [
          'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
          'startColor' => [
            'argb' => 'D9E1F2',
          ],
        ],
      ];
      $sheet->getStyle('A' . $j . ':' . $last_column_string . $j)->applyFromArray($styleArray);
    }

    //Format all the table
    $styleArray = [
      'borders' => [
        'allBorders' => [
          'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
        ],
      ],
      'alignment' => [
        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
      ]
    ];
    $sheet->getStyle('A1:' . $last_column_string . $rows_count)->applyFromArray($styleArray);
  }
}


/**
 * Get all the product rows in an array
 */
function pq_get_product_rows($orders) {

  $product_rows = array();
    
  foreach ( $orders as $order ) {
    foreach ( $order->get_items() as $item_id => $item ) {
      $product = wc_get_product( $item->get_product_id() );

      if ( myfct_is_relevant_product( $product ) ) {

        if ( $item->get_variation_id() !== 0 ) {
          $product_id = $item->get_variation_id();
          $parent_id = $product->get_id();
        } else {
          $product_id = $parent_id = $product->get_id();
        }

        $product_quantity_before_refund = $item->get_quantity();
        $product_quantity_refunded = $order->get_qty_refunded_for_item( $item_id );
        $product_quantity = $product_quantity_before_refund + $product_quantity_refunded;
        $lot_quantity = get_post_meta( $product_id, '_lot_quantity', true );
        $total_quantity = $product_quantity * $lot_quantity;

        $short_name = get_post_meta( $product_id, '_short_name', true );

        $requires_new_row = true;

        foreach ( $product_rows as $existing_product_id => $product_row ) {

          $existing_short_name = $product_rows[$existing_product_id]['_short_name'];

          if ( $product_id == $existing_product_id || $short_name == $existing_short_name ) {
            $product_rows[$existing_product_id]['total_quantity'] += $total_quantity;
            $requires_new_row = false;
          }
        }

        if ( $requires_new_row ) {
          $weight = get_post_meta( $product_id, '_pq_weight', true );
          $unit = get_post_meta( $product_id, '_lot_unit', true );
          $weight_with_unit = $weight . $unit;
          $packing_priority = get_post_meta( $parent_id, '_packing_priority', true );

          $tags = wp_get_post_terms( $parent_id, 'pq_distributor', array( 'fields' => 'names' ) );
          if ( empty( $tags ) ) {
            $tags = wp_get_post_terms( $parent_id, 'product_tag', array( 'fields' => 'names' ) );
            if ( empty( $tags ) ) {
              $tags = wp_get_post_terms( $parent_id, 'pq_producer', array( 'fields' => 'names' ) );
            }
          }
          $tags_string = implode( ', ', $tags );

          $inventory_type = wp_get_post_terms( $parent_id, 'pq_inventory_type', array( 'fields' => 'slugs' ) );

          $product_rows[$product_id] = array(
            'supplier' => $tags_string,
            '_short_name' => $short_name,
            'total_quantity' => $total_quantity,
            'weight' => $weight_with_unit,
            '_packing_priority' => $packing_priority,
            'pq_inventory_type' => $inventory_type,
          );
        }
      }
    }
  }

  $columns = array_column($product_rows, '_short_name');
  array_multisort($columns, SORT_ASC, SORT_STRING, $product_rows);
  $columns = array_column($product_rows, 'supplier');
  array_multisort($columns, SORT_ASC, SORT_STRING, $product_rows);

  return $product_rows;
}


/**
 * Export the excel file
 */
function pq_export_excel($spreadsheet) {
  $timezone = new DateTimeZone( get_option( 'timezone_string' ) );
  $now = new DateTime( '', $timezone );
  $filename = 'listes-operations_' . $now->format( 'Y-m-d' ) . '.xlsx';

  $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
  header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  header('Content-Disposition: attachment; filename="'. urlencode($filename).'"');
  header( 'Cache-Control: no-cache' );
  header( "Expires: 0" );

  while (ob_get_level()) {
      ob_end_clean();
  }
  $writer->save('php://output');
  exit;
}

/**
 * Export the labels for daily operations
 */
function pq_export_labels() {

  $timezone = new DateTimeZone( get_option( 'timezone_string' ) );
  $default_date_obj = new DateTime( 'today', $timezone );
  $default_date = $default_date_obj->format( 'Y-m-d' );
  $orders = myfct_get_relevant_orders( $default_date );

  $pdf_array = array();

  foreach ( $orders as $order ) {
    $order_id = $order->get_id();
    $url = 'https://api.track-pod.com/Order/Number/' . $order_id;
  
    $response = wp_remote_get( $url, array(
      'method' => 'GET',
      'httpversion' => '1.0',
      'headers' => array(
        'Content-Type' => 'application/json',
        'X-API-KEY' => '534f2b64-1171-40a2-9942-b4a6c2c8e61b',
      ),
    ));
  
    $trackpod_data = json_decode($response['body']);
  
    $route_no = $trackpod_data->RouteNumber;
    $order_sequence = $trackpod_data->SeqNumber;
    $route_no_full = $route_no . '--' . sprintf("%02d", $order_sequence);

    $order_meta = array();

    if ( ! empty($order->get_shipping_address_2()) ) { //If there is apt number
			$full_delivery_address = $order->get_shipping_address_1() . ', ' . $order->get_shipping_address_2() . ', ' . $order->get_shipping_city() . ', ' . $order->get_shipping_postcode() . ', ' . $order->get_shipping_country();
		} else { //Without apt number
			$full_delivery_address = $order->get_shipping_address_1() . ', ' . $order->get_shipping_city() . ', ' . $order->get_shipping_postcode() . ', ' . $order->get_shipping_country();
		}

    $pickup_location_meta =  get_post_meta($order_id, 'pq_pickup_datetime', true);
		if ( empty($pickup_location_meta) ) {
			$delivery_address = $full_delivery_address;
      if ( empty($order->get_billing_company()) ) {
        $order_meta['delivery_type'] = 'delivery';
      } else {
        $order_meta['delivery_type'] = 'business';
      }
		} else {
			$shipping_items = $order->get_items( 'shipping' );
			$shipping_item = reset($shipping_items);
			
			$pickup_location_adress = $shipping_item->get_meta( '_pickup_location_address' );
			$delivery_address = $pickup_location_adress['address_1'] . ', ' . $pickup_location_adress['city'] . ', ' . $pickup_location_adress['state'] . ', ' . $pickup_location_adress['postcode'] . ', ' . $pickup_location_adress['country'];

      $order_meta['delivery_type'] = 'pickup';
		}

		$client_name = $order->get_formatted_shipping_full_name();
		$phone = $order->get_billing_phone();
		$delivery_note = sanitize_text_field( $order->get_customer_note() );

		$order_date = $order->get_date_created();
    $email = $order->get_billing_email();
		$is_first_order = pq_is_first_order($email, $order_date);

    if ($is_first_order) {
      $order_meta['is_first_order'] = true;
    } else {
      $order_meta['is_first_order'] = false;
    }

    //Get order items
    $product_lines = array();
    $order_meta['has_special_product'] = false;

    foreach( $order->get_items() as $item_id => $item ) {
			$product_id = $item->get_product_id();
			$product = wc_get_product( $product_id );

			//Get only products to add to export
			if ( myfct_is_relevant_product($product) ) {
								
				//Get item info
				$item_quantity_before_refund = $item->get_quantity();
				$item_quantity_refunded = $order->get_qty_refunded_for_item( $item_id );
				$item_quantity = $item_quantity_before_refund + $item_quantity_refunded;

        $is_special_item = get_post_meta($product_id, '_pq_special_delivery', true);
        if ( ! empty($is_special_item) ) {
          $order_meta['has_special_product'] = true;
        }

				if ( $item->get_variation_id() !== 0 ) {
					$variation_id = $item->get_variation_id();
					$product_short_name = get_post_meta($variation_id, '_short_name', true);
					$product_lot_quantity = get_post_meta($variation_id, '_lot_quantity', true);
					$product_weight = get_post_meta( $variation_id, '_pq_weight', true );
					$product_unit = get_post_meta( $variation_id, '_lot_unit', true );
					$product_weight_with_unit = $product_weight . $product_unit;
				} else {
					$product_short_name = get_post_meta($product_id, '_short_name', true);
					$product_lot_quantity = get_post_meta($product_id, '_lot_quantity', true);
					$product_weight = get_post_meta( $product_id, '_pq_weight', true );
					$product_unit = get_post_meta( $product_id, '_lot_unit', true );
					$product_weight_with_unit = $product_weight . $product_unit;
				}

				$product_packing_priority = get_post_meta($product_id, '_packing_priority', true);

        if ( $product_lot_quantity == 1 ) {
          $product_lot_quantity = '';
        }

        $product_line = array( array( 
          'product_short_name' => utf8_decode($product_short_name),
          'item_quantity' => $item_quantity,
          'product_lot_quantity' => $product_lot_quantity,
          'packing_priority' => $product_packing_priority,
        ));

        $product_lines = array_merge($product_lines, $product_line);
      }
    }

    $columns = array_column($product_lines, 'product_short_name');
    array_multisort($columns, SORT_ASC, SORT_STRING, $product_lines);
    $columns = array_column($product_lines, 'packing_priority');
    array_multisort($columns, SORT_ASC, SORT_NUMERIC, $product_lines);

    $order_array = array( array( 
      'route_no_full' => $route_no_full,
      'order_id' => '#' . $order_id,
      'client_name' => utf8_decode($client_name),
      'phone' => utf8_decode($phone),
      'full_delivery_address' => utf8_decode($delivery_address),
      'delivery_note' => utf8_decode($delivery_note),
      'order_meta' => $order_meta,
      'product_lines' => $product_lines,
    ));

    $pdf_array = array_merge($pdf_array, $order_array);
  }

  $columns = array_column($pdf_array, 'route_no_full');
  array_multisort($columns, SORT_ASC, SORT_STRING, $pdf_array);

  foreach ( $pdf_array as $key => $order_array ) {
    if ( $key > 0 ) {
      if ($order_array['route_no_full'] == $pdf_array[$key - 1]['route_no_full'] ) {
        $pdf_array[$key - 1]['route_no_full'] .= ' A';
        $pdf_array[$key]['route_no_full'] .= ' B';
      }
    }
  }
  
  while (ob_get_level()) {
    ob_end_clean();
  }
  require_once 'pq-fpdf-functions.php';
  $pdf = new PQ_FPDF();
  $pdf->SetFont('Arial', 'B', 12);

  $margin = 10;
  $pdf->margin = $margin;
  $pdf->SetMargins($margin, 25);
  $padding = 2;
  $pdf->padding = $padding;
  $page_width = $pdf->GetPageWidth() - 2 * $margin;
  $page_height = $pdf->GetPageHeight() - 2 * $margin;

  $pdf->SetStyle('main', 'Arial', 'N', 12, '', 0);
  $pdf->SetStyle('large', 'Arial', 'B', 14, '', 0);
  $pdf->SetStyle('note', 'Arial', 'N', 10, '', 0);
  $pdf->SetStyle('top_icon', 'Arial', 'B', 20, '');

  $pdf->SetStyle('black', '', '', 0, '0, 0, 0');
  $pdf->SetStyle('red', '', '', 0, '255, 51, 51');
  $pdf->SetStyle('blue', '', '', 0, '0, 0, 204');
  $pdf->SetStyle('green', '', '', 0, '0, 153, 0');
  $pdf->SetStyle('purple', '', '', 0, '127, 0, 255');

  $delivery_info_cell_width = $page_width / 3 - 2 * $padding;
  $delivery_info_cell_height = 4;
  $delivery_info_columns = 3;
  
  foreach ($pdf_array as $order_array) {
    $pdf->AddPage();
    $y_top_label = $pdf->GetY();
    $top_label_html = '';

    $top_label_icons_html = '';
    if ( $order_array['order_meta']['has_special_product'] )
        $top_label_icons_html .= '<top_icon>(!)</top_icon>';

    if ( $order_array['order_meta']['is_first_order'] )
      $top_label_icons_html .= '<top_icon>(1st)</top_icon>';

    switch ($order_array['order_meta']['delivery_type']) {
      case 'pickup' :
        $label_color = 'red';
        break;
      case 'business' :
        $label_color = 'purple';
        break;
      default :
        $label_color = 'black';
    }

    $top_label_html .= '<' . $label_color . '>';

    foreach ($order_array as $info_type => $item_line) {

      if ( ! empty($item_line) && $info_type != 'product_lines' && $info_type != 'order_meta') {

        switch ( $info_type ) {
          case 'route_no_full' :
            $tag = 'large';
            break;
          case 'order_id' :
            $tag = 'large';
            break;
          case 'delivery_note' :
            $tag = 'note';
            break;
          default:
          $tag = 'main';
        }
        $top_label_html .= '<' . $tag . '>' . $item_line . '</' . $tag . '>';
      }
    }
    $top_label_html .= '</' . $label_color . '>';

    for ( $i = 1; $i <= $delivery_info_columns; $i++ ) {
      $x = ($i - 1) * ($delivery_info_cell_width + $padding) + $margin;
      $pdf->SetXY( $x, $y_top_label);
      
      if ( !empty($top_label_icons_html) )
        $pdf->WriteTag($delivery_info_cell_width, 16, $top_label_icons_html, 0, 'C');

      $pdf->SetXY( $x, $pdf->GetY() );
      $pdf->WriteTag($delivery_info_cell_width, $delivery_info_cell_height, $top_label_html, 0, 'C');
    }

    $product_info_cell_width = $page_width / 3 - 2 * $padding;
    $pdf->col_width = $product_info_cell_width;
    $product_info_cell_height = 7;
    
    $pdf->SetX( $margin );
    $horizontal_line_y = $pdf->GetY();
    $pdf->Line(0, $horizontal_line_y, $pdf->GetPageWidth(), $horizontal_line_y);

    $pdf->SetFont('Arial', 'B', 18);
    $pdf->Cell( $page_width, 10, $order_array['route_no_full'], 0, 2, 'C' );

    $top_products_y = $pdf->GetY();
    $pdf->y0 = $top_products_y;
    $pdf->SetCol(0);

    foreach ( $order_array['product_lines'] as $product_info ) {

      if ( $pdf->col > 0 && $pdf->col !== $previous_col ) {
        $pdf->Line($pdf->col_left_x - $padding / 2, $horizontal_line_y, $pdf->col_left_x - $padding / 2, $pdf->GetPageHeight());
      }
      $previous_col = $pdf->col;

      if ( $product_info['item_quantity'] !== 1 ) {
        $item_quantity_color = 'red';
      } else {
        $item_quantity_color = 'black';
      }

      if ( ! empty($product_info['product_lot_quantity']) ) {
        $product_lot_quantity_color = 'red';
      } else {
        $product_lot_quantity_color = 'black';
      }

      if ( $product_info['packing_priority'] >= 20 ) {
        $product_short_name_color = 'blue';
      } elseif ( strpos($product_info['product_short_name'], 'BIO') !== false ) {
        $product_short_name_color = 'green';
      } else {
        $product_short_name_color = 'black';
      } 

      $product_line_html = '';
      $product_line_html .= '<main>'; 
      $product_line_html .= '<' . $item_quantity_color . '>' . $product_info['item_quantity'] . 'x </' . $item_quantity_color . '>';

      if ( ! empty($product_info['product_lot_quantity']) )
        $product_line_html .= '<' . $product_lot_quantity_color . '>' . $product_info['product_lot_quantity'] . ' </' . $product_lot_quantity_color . '>';

      $product_line_html .= '<' . $product_short_name_color . '>' . $product_info['product_short_name'] . '</' . $product_short_name_color . '>';
      $product_line_html .= '</main>';
      $pdf->SetX($pdf->col_left_x);
      $pdf->WriteTag($product_info_cell_width, $product_info_cell_height, $product_line_html, 0, 'L');
    }
  }

  $pdf->Output();
}