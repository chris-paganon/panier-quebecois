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

    if ( isset( $_POST[ 'cold_labels_export_form' ] ) && check_admin_referer( 'cold_labels_export_clicked' ) ) {
        pq_export_cold_labels();
    }

    ob_start();
		wc_pq_get_template( 'admin/pq-export-operations-content.php', '' );
		return ob_get_clean();
  }
}


/**
 * 
 * Export operations excel sheets
 * 
 */

/**
 * Export all the lists for daily operations
 */
function pq_export_operations_lists() {
  $orders = pq_get_relevant_orders_today();
  $product_rows = pq_get_product_rows($orders);
  $product_rows = pq_add_quantity_to_buy_to_products($product_rows);

  $short_name_columns = array_column($product_rows, '_short_name');
  $supplier_column = array_column($product_rows, 'supplier');
  array_multisort($supplier_column, SORT_ASC, SORT_STRING, $short_name_columns, $product_rows);

  $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

  pq_print_sheets($spreadsheet, $product_rows);

  pq_style_sheets($spreadsheet);

  pq_export_excel($spreadsheet, 'listes-operations');
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

  $to_print = array('_short_name', 'total_quantity', '_pq_operation_stock', 'quantity_to_buy');

  $centrale_sheet->setCellValue('A1', 'Nom court');
  $centrale_sheet->setCellValue('B1', 'Conso');
  $centrale_sheet->setCellValue('C1', 'Stock');
  $centrale_sheet->setCellValue('D1', 'Besoin');

  pq_print_on_sheet( $centrale_sheet, $product_rows, 10, 19, $to_print );

  //Print Centrale en inventaire à l'exterieur
  $centrale_stock_sheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'Centrale en stock');
  $spreadsheet->addSheet($centrale_stock_sheet, 0);

  $to_print = array('_short_name', 'total_quantity', '_pq_operation_stock', 'quantity_to_buy');
  $products_to_print = 'centrale-exterieur';

  $centrale_stock_sheet->setCellValue('A1', 'Nom court');
  $centrale_stock_sheet->setCellValue('B1', 'Conso');
  $centrale_stock_sheet->setCellValue('C1', 'Stock');
  $centrale_stock_sheet->setCellValue('D1', 'Besoin');

  pq_print_on_sheet( $centrale_stock_sheet, $product_rows, 1, 999, $to_print, $products_to_print );

  //Print Pesée
  $peser_sheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'Peser');
  $spreadsheet->addSheet($peser_sheet, 0);

  $to_print = array('_short_name', 'total_quantity', 'weight', '_pq_operation_stock');
  $products_to_print = 'a-peser';

  $peser_sheet->setCellValue('A1', 'Nom court');
  $peser_sheet->setCellValue('B1', 'Conso');
  $peser_sheet->setCellValue('C1', 'Poids');
  $peser_sheet->setCellValue('D1', 'Stock');

  pq_print_on_sheet( $peser_sheet, $product_rows, 1, 999, $to_print, $products_to_print );
}


/**
 * 
 * Export operations PDF documents
 * 
 */

/**
 * Export the labels for daily operations
 */
function pq_export_labels() {

  $orders = pq_get_relevant_orders_today();
  $pdf_array = pq_get_pdf_array($orders);
  
  pq_print_labels_pdf($pdf_array);
}


/**
 * Export cold labels for daily operations
 */
function pq_export_cold_labels() {

  $orders = pq_get_relevant_orders_today();
  $pdf_array = pq_get_pdf_array($orders);
  
  pq_print_cold_labels_pdf($pdf_array);
}


/**
 * Get array to build labels PDF
 */
function pq_get_pdf_array( $orders ) {
  $pdf_array = array();

  foreach ( $orders as $order ) {
    
    $order_array = pq_get_orders_info_array( $order );
    $order_array['product_lines'] = pq_get_product_lines_array( $order );
    $orders_array = array( $order_array );

    $pdf_array = array_merge($pdf_array, $orders_array);
  }

  $columns = array_column($pdf_array, 'route_no_full');
  array_multisort($columns, SORT_DESC, SORT_STRING, $pdf_array);

  $pdf_array = pq_fix_same_address_sequence($pdf_array);

  return $pdf_array;
}


/**
 * Add letters sequence to route numbers sequence if several orders at same address
 */
function pq_fix_same_address_sequence( $pdf_array ) {
  $alphabet = range('A', 'Z');
  $i = 0;
  $previous_route_no = '';

  foreach ( $pdf_array as $key => $order_array ) {
    if ( $order_array['route_no_full'] == $previous_route_no ) {
      $pdf_array[$key - 1]['route_no_full'] .= ' ' . $alphabet[$i];
      $i++;
    } elseif ( $order_array['route_no_full'] !== $previous_route_no && $i !== 0 ) {
      $pdf_array[$key - 1]['route_no_full'] .= ' ' . $alphabet[$i];
      $i = 0;
    } else {
      $i = 0;
    }
    $previous_route_no = $order_array['route_no_full'];
  }

  return $pdf_array;
}


/**
 * Print the main labels
 */
function pq_print_labels_pdf( $pdf_array ) {

  $pdf = pq_set_new_labels_pdf();

  $pdf->max_col = 3;

  $pdf->SetStyle('main', 'Arial', 'N', 12, '', 0);
  $pdf->SetStyle('large', 'Arial', 'B', 14, '', 0);
  $pdf->SetStyle('note', 'Arial', 'N', 10, '', 0);
  $pdf->SetStyle('top_icon', 'Arial', 'B', 20, '');

  $pdf->SetStyle('black', '', '', 0, '0, 0, 0');
  $pdf->SetStyle('red', '', '', 0, '255, 51, 51');
  $pdf->SetStyle('blue', '', '', 0, '0, 0, 204');
  $pdf->SetStyle('green', '', '', 0, '0, 153, 0');
  $pdf->SetStyle('purple', '', '', 0, '127, 0, 255');
  
  foreach ($pdf_array as $order_array) {
    pq_print_top_labels($pdf, $order_array);
    pq_print_products_list($pdf, $order_array);
  }

  $pdf->Output();
}


/**
 * Print top part of main labels
 */
function pq_print_top_labels($pdf, $order_array) {

  $pdf->AddPage();
  $y_top_label = $pdf->GetY();
  $top_label_html = '';

  $delivery_info_cell_width = $pdf->page_width / $pdf->max_col - ($pdf->max_col - 1) * $pdf->padding;
  $delivery_info_cell_height = 4;
  $delivery_info_columns = 3;
  
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
    $x = ($i - 1) * ($delivery_info_cell_width + $pdf->padding) + $pdf->margin;
    $pdf->SetXY( $x, $y_top_label);
    
    if ( !empty($top_label_icons_html) )
      $pdf->WriteTag($delivery_info_cell_width, 16, $top_label_icons_html, 0, 'C');

    $pdf->SetXY( $x, $pdf->GetY() );
    $pdf->WriteTag($delivery_info_cell_width, $delivery_info_cell_height, $top_label_html, 0, 'C');
  }
}


/**
 * Print products list
 */
function pq_print_products_list($pdf, $order_array, $is_cold_labels = false) {

  $product_info_cell_width = $pdf->page_width / $pdf->max_col - ($pdf->max_col - 1) * $pdf->padding;
  $pdf->col_width = $product_info_cell_width;
  if ($is_cold_labels) {
    $product_info_cell_height = 10;
  } else {
    $product_info_cell_height = 7;
  }
  
  $pdf->SetX( $pdf->margin );
  $horizontal_line_y = $pdf->GetY();
  $pdf->Line(0, $horizontal_line_y, $pdf->GetPageWidth(), $horizontal_line_y);

  $pdf->SetFont('Arial', 'B', 18);
  if ( ! $is_cold_labels ) {
    $pdf->Cell( $pdf->page_width, 10, $order_array['route_no_full'], 0, 2, 'C' );
  }

  $top_products_y = $pdf->GetY();
  $pdf->y0 = $top_products_y;
  $pdf->SetCol(0);
  $previous_col = $pdf->col;

  foreach ( $order_array['product_lines'] as $product_info ) {

    if ( $is_cold_labels && $product_info['packing_priority'] < 20 ) continue;

    if ( $pdf->col > 0 && $pdf->col !== $previous_col ) {
      $pdf->Line($pdf->col_left_x - $pdf->padding / ($pdf->max_col - 1), $horizontal_line_y, $pdf->col_left_x - $pdf->padding / ($pdf->max_col - 1), $pdf->GetPageHeight());
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

    if ( $product_info['packing_priority'] >= 20 && ! $is_cold_labels ) {
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


/**
 * Print the cold labels
 */
function pq_print_cold_labels_pdf( $pdf_array ) {

  $pdf = pq_set_new_labels_pdf();

  $pdf->max_col = 2;

  $pdf->SetStyle('main', 'Arial', 'B', 18, '', 0);
  $pdf->SetStyle('large', 'Arial', 'B', 14, '', 0);
  $pdf->SetStyle('note', 'Arial', 'N', 10, '', 0);
  $pdf->SetStyle('top_icon', 'Arial', 'B', 20, '');

  $pdf->SetStyle('black', '', '', 0, '0, 0, 0');
  $pdf->SetStyle('red', '', '', 0, '255, 51, 51');
  $pdf->SetStyle('blue', '', '', 0, '0, 0, 204');
  $pdf->SetStyle('green', '', '', 0, '0, 153, 0');
  $pdf->SetStyle('purple', '', '', 0, '127, 0, 255');
  
  foreach ($pdf_array as $order_array) {
    pq_print_cold_labels_header($pdf, $order_array);
    pq_print_products_list($pdf, $order_array, true);
  }

  $pdf->Output();
}


/**
 * Print the cold labels header
 */
function pq_print_cold_labels_header($pdf, $order_array) {
  $pdf->AddPage();
  $pdf->SetTextColor(255);
  $pdf->SetFont('Arial', 'B', 18);
  $pdf->Cell( $pdf->page_width, 10, $order_array['route_no_full'], 0, 2, 'C' );
  $pdf->Cell( $pdf->page_width, 10, $order_array['order_id'], 0, 2, 'C' );
  $pdf->SetFont('Arial', '', 12);
  $pdf->Cell( $pdf->page_width, 8, $order_array['client_name'], 0, 2, 'C' );
  $pdf->MultiCell( $pdf->page_width, 8, $order_array['delivery_note'], 0, 'C' );
}