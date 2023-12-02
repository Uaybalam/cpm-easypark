<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Client;
use thiagoalessio\TesseractOCR\TesseractOCR;
use Illuminate\Support\Facades\Http;
use App\Models\Category;
use App\Models\Customer;
class ImageController extends Controller
{


    public function index()
    {
        $arduinoIp = '192.168.132.201';

        // Configura el cliente Guzzle con opciones específicas para permitir HTTP/1.0 y hacer el llamdo para que la esp32 tome la foto
        $client = new Client([
            'curl' => [
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_0,
                CURLOPT_HTTPHEADER => ['Expect:', 'Connection: close'],
                CURLOPT_HEADER => false,
                CURLOPT_TIMEOUT => 1000,
            ],
        ]);

        // Intenta realizar la captura llamando a la URL /capture en tu Arduino.
        try {
            $capturaResponse = $client->get("http://$arduinoIp/capture");
            $capturaResult = $capturaResponse->getBody()->getContents();

            // Guarda la imagen en el servidor .
            $imagenPath = public_path('foto.jpg');
            file_put_contents($imagenPath, $capturaResult);

            // Verifica si la imagen se guardó correctamente antes de procesarla.
            if (file_exists($imagenPath)) {
                // Ejecuta Tesseract OCR en la imagen.
                //Esto para poder cambiar la imagen a texto
                $result = (new TesseractOCR($imagenPath))->run();
                $texto = public_path('texto.txt');
                //se almacena en un texto llamado texto.txt donde viene el texto de la imagen
                file_put_contents($texto, $result);
                // Retorna la vista con la imagen y el texto

                // Retorna la vista con la imagen y el texto
                return view('vehicles.create', [
                    'texto' => $result,
                    'categories' => Category::get(['id','name']),
                    'customers' => Customer::get(['id','name'])
                ]);



            } else {
                dd("Error al guardar la imagen.");
            }
        } catch (\Exception $e) {
            $errorMensaje = "Error al capturar: " . $e->getMessage();


            return view('vehicles.create', [
                'errorMensaje' => $errorMensaje,
                'categories' => Category::get(['id','name']),
                'customers' => Customer::get(['id','name'])
            ]);
        }



    }



}
