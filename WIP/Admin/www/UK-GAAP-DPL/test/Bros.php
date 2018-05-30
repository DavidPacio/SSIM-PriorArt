<?php
/**
 * PHPExcel
 *
 * Copyright (C) 2006 - 2011 PHPExcel
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category   PHPExcel
 * @package    PHPExcel
 * @copyright  Copyright (c) 2006 - 2011 PHPExcel (http://www.codeplex.com/PHPExcel)
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt	LGPL
 * @version    1.7.6, 2011-02-27
 */

/** Error reporting */
error_reporting(E_ALL);

date_default_timezone_set('Europe/London');

/** PHPExcel */
#equire_once '../Classes/PHPExcel.php';
require_once '../../../../Com/PHPExcel/PHPExcel.php';

$objReader = new PHPExcel_Reader_Excel2007();
$objReader->setReadDataOnly(true);
#$objReader->setLoadSheetsOnly( array("Sheet 1", "My special sheet") );
$excel = $objReader->load("Bros.xlsx");

print('<table border="1">');
for ($i = 2; $i < 15; $i++) {
    print('<tr>');
   
    print('<td>');
    print($excel->getActiveSheet()->getCell('A' . $i)->getValue()); //this is how we get a simple value
    print('</td>');
   
    print('<td>');
    print($excel->getActiveSheet()->getCell('B' . $i)->getValue());
    print('</td>');
   
    print('<td>');
    print($excel->getActiveSheet()->getCell('C' . $i)->getValue());
    print('</td>');
   
    print('<td>');
    print($excel->getActiveSheet()->getCell('D' . $i)->getCalculatedValue()); //this is how we get a calculated value
    print('</td>');
   
    print('</tr>');
}

print('<tr><td>&nbsp;</td><td>&nbsp;</td>');
print('<td>' . $excel->getActiveSheet()->getCell('C5')->getCalculatedValue() . '</td>');
print('<td>' . $excel->getActiveSheet()->getCell('D5')->getCalculatedValue() . '</td></tr>');
print('</table>');


// Echo memory peak usage
echo date('H:i:s') . " Peak memory usage: " . (memory_get_peak_usage(true) / 1024 / 1024) . " MB\r\n";

// Echo done
echo date('H:i:s') . " Done writing file.\r\n";
