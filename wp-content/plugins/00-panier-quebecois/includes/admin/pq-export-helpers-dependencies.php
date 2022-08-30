<?php

if ( !defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

/**
 * Set new PDF object and page style
 */
function pq_set_new_labels_pdf() {
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
  $pdf->page_width = $pdf->GetPageWidth() - 2 * $margin;

  return $pdf;
}


/**
 * Export the excel file
 */
function pq_export_excel($spreadsheet, $spreadsheet_name) {
  $timezone = new DateTimeZone( get_option( 'timezone_string' ) );
  $now = new DateTime( '', $timezone );
  $filename = $spreadsheet_name . '_' . $now->format( 'Y-m-d' ) . '.xlsx';

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
 *  Export the csv
 */
function myfct_export_csv( $filename, $csv ) {
  header( 'Content-Type: application/csv;charset=UTF-8' );
  header( 'Content-Disposition: attachment; filename=' . $filename );
  header( 'Cache-Control: no-cache' );
  header( "Expires: 0" );

  ob_end_clean();

  //echo "\xEF\xBB\xBF"; (solves characters issues on windows)
  //echo "sep=,\n";  (solves seperator issues on MAC OS)

  $csv_string = '';

  foreach ( $csv as $line ) {
    $csv_string .= implode( "\t", $line ) . "\n";
  }

  $csv_encoded = mb_convert_encoding( $csv_string, 'UTF-16LE', 'UTF-8' );
  echo chr( 255 ) . chr( 254 ) . $csv_encoded;

  exit;
}


/**
 * Style all the worksheets
 */
function pq_style_sheets($spreadsheet) {
  for ( $i = 0; $i <= $spreadsheet->getSheetCount() - 1; $i++ ) {
    $sheet = $spreadsheet->getSheet($i);
    $last_column_string = $sheet->getHighestDataColumn(2);
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