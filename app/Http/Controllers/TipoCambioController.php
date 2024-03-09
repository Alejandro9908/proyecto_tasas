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
        $tasas = TasaCambio::with('detalles')->orderBy('id', 'desc')->paginate(50);
        return response()->json($tasas);
    }

    public function obtenerTipoCambio($fechaInicio, $fechaFin)
    {
        $client = new SoapClient('http://www.banguat.gob.gt/variables/ws/TipoCambio.asmx?WSDL');

        $params = [
            'fechainit' => $fechaInicio, // formato dd/mm/aaaa
            'fechafin' => $fechaFin, // formato dd/mm/aaaa
        ];

        try {
            $response = $client->TipoCambioRango($params);

            // Accede a la respuesta como necesites
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
            $tasas_cambio = $tasas_cambio = $this->obtenerTipoCambio($fechaInicio, $fechaFin);

            // Calcular el promedio de venta y compra
            $ventaTotal = array_sum(array_column($tasas_cambio->Vars->Var, 'venta'));
            $compraTotal = array_sum(array_column($tasas_cambio->Vars->Var, 'compra'));
            $totalItems = $tasas_cambio->TotalItems;

            $ventaPromedio = $ventaTotal / $totalItems;
            $compraPromedio = $compraTotal / $totalItems;

            // Guardar el promedio en la base de datos
            $tasa = new TasaCambio(); // Asegúrate de que TasaCambio es el nombre correcto del modelo
            $tasa->moneda = $tasas_cambio->Vars->Var[0]->moneda;
            $tasa->fecha_inicio = $request->input('fechainit');
            $tasa->fecha_fin = $request->input('fechafin');
            $tasa->venta_promedio = $ventaPromedio;
            $tasa->compra_promedio = $compraPromedio;
            $tasa->save();

            foreach ($tasas_cambio->Vars->Var as $rg) {
                $tasa_det = new TasaCambioDetalle();
                $tasa_det->tasa_cambio_id = $tasa->id;
                $tasa_det->fecha = Carbon::createFromFormat('d/m/Y', $rg->fecha);;
                $tasa_det->venta = $rg->venta;
                $tasa_det->compra = $rg->compra;
                $tasa_det->save();
            }

            // Commit de la transacción
            DB::commit();

            // Retornar la respuesta JSON con los promedios
            return response()->json([
                'id' => $tasa->id,
                'fecha_init' => $request->input('fechainit'),
                'fecha_fin' => $request->input('fechafin'),
                'venta_promedio' => $ventaPromedio,
                'compra_promedio' => $compraPromedio
            ]);

        } catch (\Exception $e) {
            // Revertir la transacción en caso de error
            DB::rollback();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
