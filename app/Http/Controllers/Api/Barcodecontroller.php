<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class Barcodecontroller extends Controller
{
//     public function index(Request $request)
// {
//     $barcode = (new \Picqer\Barcode\Types\TypeCode128())->getBarcode('081231723897');
//     $renderer = new \Picqer\Barcode\Renderers\HtmlRenderer();
//     echo $renderer->render($barcode);
// }

public function generateBarcode(Request $request){
    dd("Ok");
}

public function index(Request $request)
{
    $barcode = (new \Picqer\Barcode\Types\TypeCode128())->getBarcode('081231723897');
    $renderer = new \Picqer\Barcode\Renderers\HtmlRenderer();
    $barcodeHtml = $renderer->render($barcode);

    return response($barcodeHtml, 200)
        ->header('Content-Type', 'text/html');
}

}
