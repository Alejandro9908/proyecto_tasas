<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTasaRequest;
use App\Models\TasaCambio;
use App\Models\TasaCambioDetalle;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use SoapClient;

class TipoCambioController extends Controller
{

    public function obtenerVariablesDisponibles()
    {
        $client = new SoapClient('http://www.banguat.gob.gt/variables/ws/TipoCambio.asmx?WSDL');

        try {
            $response = $client->VariablesDisponibles();

            // Accede a la respuesta como necesites
            $resultado = $response->VariablesDisponiblesResult;

            return response()->json($resultado);
        } catch (\Exception $e) {
            // Manejo del error
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function obtenerTasasCambio()
    {
        $tasas = TasaCambio::with('detalles')
            ->where('estado', 1)
            ->orderBy('id', 'desc')
            ->paginate(50);
        return response()->json($tasas);
    }

    public function obtenerTipoCambio($fechaInicio, $fechaFin)
    {
        $client = new SoapClient('http://www.banguat.gob.gt/variables/ws/TipoCambio.asmx?WSDL');

        $params = [
            'fechainit' => $fechaInicio,
            'fechafin' => $fechaFin,
        ];

        try {
            $response = $client->TipoCambioRango($params);
            $resultado = $response->TipoCambioRangoResult;
            return $resultado;
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store(StoreTasaRequest $request)
    {
        DB::beginTransaction();

        try {
            $fechaInicio = \DateTime::createFromFormat('Y-m-d', $request->input('fechainit'))->format('Y-d-m');
            $fechaFin = \DateTime::createFromFormat('Y-m-d', $request->input('fechafin'))->format('Y-d-m');

            // Consumir el SOAP API y obtener las tasas de cambio
            $result = $this->obtenerTipoCambio($fechaInicio, $fechaFin);

            $tasasCambio  = json_decode(json_encode($result->Vars->Var), true);
            $totalItems  = json_decode(json_encode($result->TotalItems), true);

            // Calcular el promedio de venta y compra
            $ventaTotal = array_sum(array_column($tasasCambio , 'venta'));
            $compraTotal = array_sum(array_column($tasasCambio , 'compra'));

            if ($totalItems > 1) {
                $moneda = $tasasCambio[0]['moneda'];
                $ventaPromedio = $ventaTotal / $totalItems;
                $compraPromedio = $compraTotal / $totalItems;
            } else {
                $moneda = $tasasCambio['moneda'];
                $ventaPromedio = $tasasCambio['venta'];
                $compraPromedio = $tasasCambio['compra'];
            }

            // Guardar en la base de datos
            $tasa = new TasaCambio();
            $tasa->moneda = $moneda;
            $tasa->fecha_inicio = $request->input('fechainit');
            $tasa->fecha_fin = $request->input('fechafin');
            $tasa->venta_promedio = $ventaPromedio;
            $tasa->compra_promedio = $compraPromedio;
            $tasa->save();

            if ($totalItems > 1) {
                foreach ($tasasCambio as $rg) {
                    $tasa_det = new TasaCambioDetalle();
                    $tasa_det->tasa_cambio_id = $tasa->id;
                    $tasa_det->fecha = Carbon::createFromFormat('d/m/Y', $rg['fecha']);
                    $tasa_det->venta = $rg['venta'];
                    $tasa_det->compra = $rg['compra'];
                    $tasa_det->save();
                }
            } else {
                $tasa_det = new TasaCambioDetalle();
                $tasa_det->tasa_cambio_id = $tasa->id;
                $tasa_det->fecha = Carbon::createFromFormat('d/m/Y', $tasasCambio['fecha']);
                $tasa_det->venta = $tasasCambio['venta'];
                $tasa_det->compra = $tasasCambio['compra'];
                $tasa_det->save();
            }

            DB::commit();

            return response()->json([
                'id' => $tasa->id,
                'fecha_init' => $request->input('fechainit'),
                'fecha_fin' => $request->input('fechafin'),
                'venta_promedio' => $ventaPromedio,
                'compra_promedio' => $compraPromedio
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try{
            $tasa = TasaCambio::find($id);
            $tasa->estado = 0;
            $tasa->save();

            return response()->json([
                'id' => $tasa->id
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
