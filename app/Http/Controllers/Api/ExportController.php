<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Message;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    public function export($account, $type)
    {
        $format = request('format', 'json');

        if ($type === 'properties') {
            $tenant = app('tenant');
            $data = Property::with('images')->where('tenant_id', $tenant->id)->get()->toArray();
            $filename = 'properties-export';
        } elseif ($type === 'messages') {
            $tenant = app('tenant');
            $data = Message::where('tenant_id', $tenant->id)->get()->toArray();
            $filename = 'messages-export';
        } else {
            abort(404);
        }

        if ($format === 'csv') {
            $csv = $this->arrayToCsv($data);
            return response($csv, 200, [
                'Content-Type'        => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}.csv\"",
            ]);
        }

        return response()->json($data)->header('Content-Disposition', "attachment; filename=\"{$filename}.json\"");
    }

    private function arrayToCsv(array $data)
    {
        if (empty($data)) return '';
        $output = fopen('php://temp', 'r+');
        fputcsv($output, array_keys($data[0]));
        foreach ($data as $row) fputcsv($output, array_values($row));
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        return $csv;
    }
}
