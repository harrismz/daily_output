<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Daily_output;
use App\User;
use DB;
use Carbon\Carbon;
use Excel;
use Dingo\Api\Routing\Helpers;
use JWTAuth;

class mainController extends Controller
{
    //
    public function index(Request $req){
    	
        $do = DB::table('daily_outputs');

        //kalau get ada parameter start_date, maka yang muncul adalah between start_date s/d start_date->addWeek()

        // parameter tanggal
        if( $req->start_date != null ){

            $do = $do->whereBetween('tanggal', [Carbon::createFromFormat('Y-m-d', $req->start_date ), 
                Carbon::createFromFormat('Y-m-d', $req->start_date )->addWeek() ]  );
        }

        /*parameter tanggal*/
        if( $req->tanggal != null ){

            $do = $do->where('tanggal','=', $req->tanggal ) ;
            // return $req->tanggal;
        }

        if ($req->shift != null) {
            # code...
            $do = $do->where('shift', '=', $req->shift);
        }

        if ($req->line_name != null) {
            # code...
            $do = $do->where('line_name', '=', $req->line_name);
        }

        //order by time.
        if ($req->shift == 'B') {
            # code...
        $do = $do->orderByRaw("CASE time when '16-17' then 1 when '17-18' then 2 when '18-19' then 3 when '19-20' then 4 when '20-21' then 5 when '21-22' then 6 when '22-23' then 7 when '23-24' then 8 when '00-01' then 9 when '01-02' then 10 when '02-03' then 11 else 12 end  ");
        }
        if ($req->shift == 'N') {
            # code...
        $do = $do->orderByRaw("CASE time when '06-07' then 1 when '07-08' then 2 when '08-09' then 3 when '09-10' then 4 when '10-11' then 5 when '11-12' then 6 when '12-13' then 7 when '13-14' then 8 when '14-15' then 9 when '15-16' then 10 when '16-17' then 11  when '17-18' then 12  when '18-19' then 13 else 14 end  ");
        }else{
            $do = $do->orderBy('time', 'asc');
        }

        //pagination
        if ( $req->limit !=null){
            $do = $do->paginate($req->limit);
        }else{
            $do = $do->paginate(15);
        }

        //jika jumlah $do > 0 maka $message = data found, otherwise data not found;
        if (count($do) > 0) {
            $message = 'Data found';
        }else{
            $message = 'Data not found';
        }

        //collect adalah helper laravel untuk array
        $additional_message = collect(['_meta'=> [
            'message'=>$message,
            'count'=> count($do)
        ] ]);
        //adding additional message
        $do = $additional_message->merge($do);
        //$do is object, need to changes to array first!
        $do = $do->toArray();

        //cek kalu $do->data kosong dan parameter2 tsb memenuhi, maka add.
        if( $req->tanggal != null && $req->shift != null && $req->line_name != null && $req->token != null &&
            empty($do['data']) )
        {
            //aunthenticate users id
            $currentUser = JWTAuth::parseToken()->authenticate();

            $result = $this->input_data($req->tanggal, $req->shift,$req->line_name, $currentUser->id );
            // return $result;
            $do['data'] = $result;
        }

        /*if ($req->shift == 'B') {
            $tmpData = [];
            foreach ($do['data'] as $key => $value) {

                if ($value->time == '00-01') {
                    $tmp = $value;
                    unset($do['data'][$key] );
                    array_push($do['data'], $tmp);
                }

                if ($value->time == '01-02') {
                    $tmp = $value;
                    unset($do['data'][$key] );
                    array_push($do['data'], $tmp);
                }

                $tmpData[] = $value;
            }
            $do['data']= $tmpData;
        }*/

        return $do;
    }

    public function download_csv (Request $req){
        $do = DB::table('daily_outputs');

        $do = $do->get();
        
        $fname = 'DAILY OUTPUT CONTROL SHEET.csv';

        header("Content-type: text/csv");
        header("Content-Disposition: attachment; filename=$fname");
        header("Pragma: no-cache");
        header("Expires: 0");
        
        $fp = fopen("php://output", "w");
        
        $headers = 'Line,Time,minute,tgt SOP,OSC Output,+/-,Lost Hour,Board Delay,Part Delay,EQP Trouble,Quality Prob,Bal. Prob,OTHERS,Support (must Zero),Change model,Problem, DIC,action,Leader'."\n";

        fwrite($fp,$headers);

        foreach ($do as $key => $value) {
            # code...
            $username = User::find($value->users_id);
            $username = $username['name'];
             // return $username;
            $row = [
                $value->line_name,
                $value->time,
                $value->minute,
                $value->target_sop,
                $value->osc_output,
                $value->plus_minus,
                $value->lost_hour,
                $value->board_delay,
                $value->part_delay,
                $value->eqp_trouble,
                $value->quality_problem_delay,
                $value->bal_problem,
                $value->others,
                $value->support,
                $value->change_model,
                $value->problem,
                $value->dic,
                $value->action,
                $username    
            ];
            
            fputcsv($fp, $row);
        }

        fclose($fp);    
    }

    public function input_data($tanggal, $shift, $line_name, $users_id ){
        // get parameter,        
        //make variable $time based on shift
        $shiftA = [
            ['id'=>1, 'time'=> '06-07', 'durasi'=> 60, 'jumat'=> 60 ],
            ['id'=>2, 'time'=> '07-08', 'durasi'=> 50, 'jumat'=> 50 ],
            ['id'=>3, 'time'=> '08-09', 'durasi'=> 50, 'jumat'=> 50 ],
            ['id'=>4, 'time'=> '09-10', 'durasi'=> 60, 'jumat'=> 60 ],
            ['id'=>5, 'time'=> '10-11', 'durasi'=> 50, 'jumat'=> 50 ],
            ['id'=>6, 'time'=> '11-12', 'durasi'=> 60, 'jumat'=> 60 ],
            ['id'=>7, 'time'=> '12-13', 'durasi'=> 25, 'jumat'=> 10 ],
            ['id'=>8, 'time'=> '13-14', 'durasi'=> 60, 'jumat'=> 50 ],
            ['id'=>9, 'time'=> '14-15', 'durasi'=> 60, 'jumat'=> 60 ],
            ['id'=>10, 'time'=> '15-16', 'durasi'=> 5, 'jumat'=> 30 ]

        ];

        // $shiftN = [ //default
        //     ['id' => 3, 'time' => '08-09', 'durasi' => 50, 'jumat' => 50],
        //     ['id' => 4, 'time' => '09-10', 'durasi' => 60, 'jumat' => 60],
        //     ['id' => 5, 'time' => '10-11', 'durasi' => 50, 'jumat' => 50],
        //     ['id' => 6, 'time' => '11-12', 'durasi' => 60, 'jumat' => 60],
        //     ['id' => 7, 'time' => '12-13', 'durasi' => 25, 'jumat' => 10],
        //     ['id' => 8, 'time' => '13-14', 'durasi' => 60, 'jumat' => 50],
        //     ['id' => 9, 'time' => '14-15', 'durasi' => 60, 'jumat' => 60],
        //     ['id' => 10, 'time' => '15-16', 'durasi' => 5, 'jumat' => 30],
        //     ['id' => 11, 'time' => '16-17', 'durasi' => 60, 'jumat' => 60],
        //     ['id' => 12, 'time' => '17-18', 'durasi' => 60, 'jumat' => 60]

        // ];
        
        $shiftN = [
            ['id' => 2, 'time' => '07-08', 'durasi' => 15, 'jumat' => 15],
            ['id'=>3, 'time'=> '08-09', 'durasi'=> 60, 'jumat'=> 60 ],
            ['id'=>4, 'time'=> '09-10', 'durasi'=> 60, 'jumat'=> 60 ],
            ['id'=>5, 'time'=> '10-11', 'durasi'=> 50, 'jumat'=> 50 ],
            ['id'=>6, 'time'=> '11-12', 'durasi'=> 60, 'jumat'=> 60 ],
            ['id'=>7, 'time'=> '12-13', 'durasi'=> 30, 'jumat'=> 15 ],
            ['id'=>8, 'time'=> '13-14', 'durasi'=> 60, 'jumat'=> 60 ],
            ['id'=>9, 'time'=> '14-15', 'durasi'=> 60, 'jumat'=> 60 ],
            ['id'=>10, 'time'=> '15-16', 'durasi'=> 40, 'jumat'=> 40 ],
            ['id'=>11, 'time'=> '16-17', 'durasi'=> 45, 'jumat'=> 60 ]

        ];

        $shiftB = [
            ['id'=>10, 'time'=> '15-16', 'durasi'=> 5, 'jumat'=> 30 ],
            ['id'=>11, 'time'=> '16-17', 'durasi'=> 55, 'jumat'=> 55],
            ['id'=>12, 'time'=> '17-18', 'durasi'=> 60, 'jumat'=> 60],
            ['id'=>13, 'time'=> '18-19', 'durasi'=> 25, 'jumat'=> 25],
            ['id'=>14, 'time'=> '19-20', 'durasi'=> 60, 'jumat'=> 60],
            ['id'=>15, 'time'=> '20-21', 'durasi'=> 50, 'jumat'=> 50],
            ['id'=>16, 'time'=> '21-22', 'durasi'=> 60, 'jumat'=> 60],
            ['id'=>17, 'time'=> '22-23', 'durasi'=> 40, 'jumat'=> 40],
            ['id'=>18, 'time'=> '23-24', 'durasi'=> 60, 'jumat'=> 60],
            ['id'=>19, 'time'=> '00-01', 'durasi'=> 60, 'jumat'=> 60],
            ['id'=>20, 'time'=> '01-02', 'durasi'=> 10, 'jumat'=> 10]

        ];

        if ( $shift == 'A' || $shift == 'a' ){
            $arrayShift = $shiftA;
        }else if ( $shift == 'N' || $shift == 'n' ){
            $arrayShift = $shiftN;
        }else {
            $arrayShift = $shiftB;
        }

        //looping based on shift
        $result = [];
        foreach ($arrayShift as $key => $value) {
            # code...
            //minute ambil dari durasi atau jumat, tergantung dari hari jumat atau bukan.
            $minute = ( $this->isFriday( $tanggal) ) ? $value['jumat'] : $value['durasi'] ;
            //store to database.
            $Daily_output = new Daily_output;

            $Daily_output->time = $value['time'];
            $Daily_output->minute = $minute;
            $Daily_output->users_id = $users_id;
            $Daily_output->tanggal = $tanggal;
            $Daily_output->shift = $shift;
            $Daily_output->line_name = $line_name;

            if(!$Daily_output->minute){$Daily_output->minute = 60; } //set default value for minute
            if(!$Daily_output->target_sop){$Daily_output->target_sop = 0; }
            if(!$Daily_output->osc_output){$Daily_output->osc_output = 0; }
            if(!$Daily_output->plus_minus){$Daily_output->plus_minus = 0; }
            if(!$Daily_output->lost_hour){$Daily_output->lost_hour = 0; }
            if(!$Daily_output->board_delay){$Daily_output->board_delay = 0; }
            if(!$Daily_output->part_delay){$Daily_output->part_delay = 0; }
            if(!$Daily_output->eqp_trouble){$Daily_output->eqp_trouble = 0; }
            if(!$Daily_output->quality_problem_delay){$Daily_output->quality_problem_delay = 0; }
            if(!$Daily_output->bal_problem){$Daily_output->bal_problem = 0; }
            if(!$Daily_output->others){$Daily_output->others = 0; }
            if(!$Daily_output->support){$Daily_output->support = 0; }
            if(!$Daily_output->change_model){$Daily_output->change_model = 0; }
            if(!$Daily_output->users_id){$Daily_output->users_id = null; }

            $Daily_output->save();

            $result[] = $Daily_output;
        }

        return $result;
    }

    public function add_lembur(Request $request){
        $line = [
            ["id"=>1, "name"=> '1'],
            ["id"=>2, "name"=> '2'],
            ["id"=>3, "name"=> '3'],
            ["id"=>4, "name"=> '4'],
            ["id"=>5, "name"=> '5'],
            ["id"=>6, "name"=> '6'],
            ["id"=>7, "name"=> '7'],
            ["id"=>8, "name"=> '8'],
            ["id"=>9, "name"=> '9'],
            ["id"=>10, "name"=> '10'],
            ["id"=>11, "name"=> '11'],
            ["id"=>12, "name"=> '12'],
            ["id"=>13, "name"=> '13'],
            ["id"=>14, "name"=> '14'],
            ["id"=>15, "name"=> '15'],
            ["id"=>16, "name"=> '16'],
            ["id"=>17, "name"=> '17'],
            ["id"=>18, "name"=> '18'],
            ["id"=>19, "name"=> '19'],
            ["id"=>20, "name"=> '20'],
            ["id"=>21, "name"=> '21'],
            ["id"=>22, "name"=> '22'],
            ["id"=>23, "name"=> '23'],
            ["id"=>24, "name"=> '24'],
            ["id"=>25, "name"=> '25'],
            ["id"=>26, "name"=> 'TNR.1'],
            ["id"=>27, "name"=> 'TNR.2'],
            ["id"=>28, "name"=> 'BAL.1'],
            ["id"=>29, "name"=> 'BAL.2']
        ];
        $tanggal = date('Y-m-d');
        $shift = 'A';

        foreach ($line as $key => $value) {
            $this->lembur($tanggal, $shift, $value['name'], 1 );
        }

        return [
            'status' =>"OK",
            'message' => 'OK'
        ];

    }

    public function lembur($tanggal, $shift, $line_name, $users_id ){
        // get parameter,        
        //make variable $time based on shift
        $shiftA = [
            ['id'=>11, 'time'=> '16-17', 'durasi'=> 60, 'jumat'=> 60 ],
            ['id'=>12, 'time'=> '17-18', 'durasi'=> 60, 'jumat'=> 60 ],
            ['id'=>13, 'time'=> '18-19', 'durasi'=> 60, 'jumat'=> 60 ],
            ['id'=>14, 'time'=> '19-20', 'durasi'=> 60, 'jumat'=> 60 ],
            ['id'=>15, 'time'=> '20-21', 'durasi'=> 60, 'jumat'=> 60 ],

        ];

         $shiftN = [
            ['id'=>1, 'time'=> '06-07', 'durasi'=> 60, 'jumat'=> 60 ],
            ['id'=>2, 'time'=> '07-08', 'durasi'=> 60, 'jumat'=> 60 ],
            ['id'=>12, 'time'=> '17-18', 'durasi'=> 60, 'jumat'=> 60 ],
            ['id'=>13, 'time'=> '18-19', 'durasi'=> 60, 'jumat'=> 60 ],
            ['id'=>14, 'time'=> '19-20', 'durasi'=> 60, 'jumat'=> 60 ],
        ];

        $shiftB = [
            ['id'=>21, 'time'=> '02-03', 'durasi'=> 60, 'jumat'=> 60],
            ['id'=>22, 'time'=> '03-04', 'durasi'=> 60, 'jumat'=> 60],
            ['id'=>23, 'time'=> '04-05', 'durasi'=> 60, 'jumat'=> 60]

        ];

        if ( $shift == 'A' || $shift == 'a' ){
            $arrayShift = $shiftA;
        }else if ( $shift == 'N' || $shift == 'n' ){
            $arrayShift = $shiftN;
        }else {
            $arrayShift = $shiftB;
        }

        //looping based on shift
        $result = [];
        foreach ($arrayShift as $key => $value) {
            # code...
            //minute ambil dari durasi atau jumat, tergantung dari hari jumat atau bukan.
            $minute = ( $this->isFriday( $tanggal) ) ? $value['jumat'] : $value['durasi'] ;
            //store to database.
            $Daily_output = new Daily_output;

            $Daily_output->time = $value['time'];
            $Daily_output->minute = $minute;
            $Daily_output->users_id = $users_id;
            $Daily_output->tanggal = $tanggal;
            $Daily_output->shift = $shift;
            $Daily_output->line_name = $line_name;

            if(!$Daily_output->minute){$Daily_output->minute = 60; } //set default value for minute
            if(!$Daily_output->target_sop){$Daily_output->target_sop = 0; }
            if(!$Daily_output->osc_output){$Daily_output->osc_output = 0; }
            if(!$Daily_output->plus_minus){$Daily_output->plus_minus = 0; }
            if(!$Daily_output->lost_hour){$Daily_output->lost_hour = 0; }
            if(!$Daily_output->board_delay){$Daily_output->board_delay = 0; }
            if(!$Daily_output->part_delay){$Daily_output->part_delay = 0; }
            if(!$Daily_output->eqp_trouble){$Daily_output->eqp_trouble = 0; }
            if(!$Daily_output->quality_problem_delay){$Daily_output->quality_problem_delay = 0; }
            if(!$Daily_output->bal_problem){$Daily_output->bal_problem = 0; }
            if(!$Daily_output->others){$Daily_output->others = 0; }
            if(!$Daily_output->support){$Daily_output->support = 0; }
            if(!$Daily_output->change_model){$Daily_output->change_model = 0; }
            if(!$Daily_output->users_id){$Daily_output->users_id = null; }

            $Daily_output->save();

            $result[] = $Daily_output;
        }

        return $result;
    }


    public function isFriday($tanggal){
        $timestamp = strtotime($tanggal); //buat object tanggal php
        $day = date('w', $timestamp); // convert ke hari ke berapa. sunday = 0, saturday = 6;
        $result = ($day == 5 ) ? true : false ; // jika $day == jumat atau 5, maka return true;
        return $result;        
    }

    public function download (Request $req)
    {
        # code...
        $do = DB::table('daily_outputs');
        
        /*get tanggal*/
        if($req->tanggal != null){
            $do= $do->where('tanggal','=', $req->tanggal);
        }

        /*get shift*/
        if($req->shift != null){
            $do= $do->where('shift','=', $req->shift);
        }

        /*get line name*/
        if($req->line_name != null){
            $do= $do->where('line_name','=', $req->line_name);
        }


        // get tanggal between
        if($req->start_date != null && $req->end_date != null ){
            
            $start_date=Carbon::createFromFormat('Y-m-d', $req->start_date );
            $end_date = Carbon::createFromFormat('Y-m-d', $req->end_date );

            $do = $do->whereBetween('tanggal', [ $start_date , 
                $end_date ]  );
        }        

        $do = $do->get();

        // return count($do);

        $data = [];
        $i = 0;
        foreach ($do as $key => $value) {
            # code...

            $username = User::find($value->users_id);
            $username = $username['name'];

            $row = [
                "Line"=> $value->line_name,
                "Time"=> $value->time,
                "minute"=> $value->minute,
                "tgt SOP"=> $value->target_sop,
                "OSC Output"=> $value->osc_output,
                "+ / -"=> $value->plus_minus,
                "Lost Hour"=> $value->lost_hour,
                "Board Delay"=> $value->board_delay,
                "Part Delay"=> $value->part_delay,
                "EQP Trouble"=> $value->eqp_trouble,
                "Quality Prob"=> $value->quality_problem_delay,
                "Bal Prob"=> $value->bal_problem,
                "OTHERS"=> $value->others,
                "Support (must Zero)"=> $value->support,
                "Change model"=> $value->change_model,
                "Problem"=> $value->problem,
                "DIC"=> $value->dic,
                "action"=> $value->action,
                "tanggal" => $value->tanggal,
                "Leader"=> $username    
            ];

            if( ($i % 10 == 0) and ($i != 0) ){
               if($i == 10){ //perulangan pertama
                $awal = 2;
                $akhir = 11;
               }else{ //sisanya
                $awal = $awal + 11;
                $akhir = $akhir + 11;
               }
               //username ganti, 
               if (!isset( $do[$awal]) ) {
                   continue;
               }
               
               $username = User::find($do[$awal]->users_id);
               $username = $username['name'];

               $total = [
                    "Line"=> "",
                    "Time"=> "Total",
                    "minute"=> "=sum(C".$awal.":C".$akhir.")",
                    "tgt SOP"=> "",
                    "OSC Output"=> "",
                    "+ / -"=> "",
                    "Lost Hour"=> "",
                    "Board Delay"=> "=sum(H".$awal.":H".$akhir.")" ,
                    "Part Delay"=> "=sum(I".$awal.":I".$akhir.")",
                    "EQP Trouble"=> "=sum(J".$awal.":J".$akhir.")",
                    "Quality Prob"=> "=sum(K".$awal.":K".$akhir.")",
                    "Bal Prob"=> "=sum(L".$awal.":L".$akhir.")",
                    "OTHERS"=> "=sum(M".$awal.":M".$akhir.")",
                    "Support (must Zero)"=> "=sum(N".$awal.":N".$akhir.")",
                    "Change model"=> "=sum(O".$awal.":O".$akhir.")",
                    "Problem"=> "",
                    "DIC"=> "",
                    "action"=> "",
                    "tanggal" => $do[$awal]->tanggal,
                    "Leader"=> $username 
                ];

                $data[] = $total;

            }

            $data[] = $row;
            
            

            $i++; //add counter
        }
        $range = 'A1:T'.(count($data)+1);
       
        Excel::create('DAILY OUTPUT CONTROL SHEET', function($excel  ) use ($data, $range ){
            // Call writer methods header_remove()
            $excel->sheet('daily output', function($sheet) use($data, $range) {
                
                /*setting default font style*/
                $sheet->setStyle(array(
                    'font' => array(
                        'name'      =>  'Calibri',
                        'size'      =>  8
                    )
                ));
                
                /*write data to excel*/
                $sheet->fromArray($data)
                      ->setBorder($range , 'thin');      

                $sheet->row(1, function($row) { 
                    $row->setBackground('#CCCCCC');
                    $row->setFont(array(
                        'family'     => 'Calibri',
                        'size'       => '8',
                        'bold'       =>  true
                    ));
                });

                $sheet->row(function ($row){
                    $i = 0;
                    $row->each(function($row){
                        if($i % 11 == 0 and $i != 0){
                            $row->setBackground('#5a6068');
                            $this->setBackground('#5a6068');
                        }
                        $i++;
                    });
                });
                
                
                //set background color for column delay type
                $sheet->cells('H1:O1', function($cells) {
                    // manipulate the range of cells
                    $cells->setBackground('#f4a445'); /*orange*/
                    $cells->setTextRotation(90);
                })->setWidth(array(
                    'I' => 5 ,
                    'J' => 5 ,
                    'K' => 5 ,
                    'L' => 5 ,
                    'M' => 5 ,
                    'N' => 5 ,
                    'O' => 5 ,
                    'P' => 5 ,
                ));
                // freeze column header
                $sheet->freezeFirstRow();
            });

        })->download('xls');
    }
    
    public function show(Request $req){
    	$id = $req->id;
		$do = Daily_output::find($id);
		

		if ($do != null)
		{
			$message = 'Data found';
		}else{
            $message = 'Data not found';
        }



        return [
            '_meta'=>[
                'message'=> $message ,
                'count'=> ($do == null) ? 0 : 1,
            ],

            'data'=> $do
        ]; 
    }

    public function store(Request $req)
    {
        $currentUser = JWTAuth::parseToken()->authenticate();

    	$Daily_output = new Daily_output;
    	$Daily_output->line_name = $req->input('line_name', null);
    	$Daily_output->time = $req->input('time', null);
    	$Daily_output->minute = $req->input('minute', null);
    	$Daily_output->target_sop = $req->input('target_sop', null);
    	$Daily_output->osc_output = $req->input('osc_output', null);
    	$Daily_output->plus_minus = $req->input('plus_minus', null);
    	$Daily_output->lost_hour = $req->input('lost_hour', null);
        //new column
        $Daily_output->board_delay = $req->input('board_delay', null);
        $Daily_output->part_delay = $req->input('part_delay', null);
        $Daily_output->eqp_trouble = $req->input('eqp_trouble', null);
        $Daily_output->quality_problem_delay = $req->input('quality_problem_delay', null);
        $Daily_output->bal_problem = $req->input('bal_problem', null);
        $Daily_output->others = $req->input('others', null);
        $Daily_output->support = $req->input('support', null);
        $Daily_output->change_model = $req->input('change_model', null);
        //end new column
    	$Daily_output->delay_type = $req->input('delay_type', null);
        $Daily_output->problem = $req->input('problem', null);
    	$Daily_output->dic = $req->input('dic', null);
    	$Daily_output->action = $req->input('action', null);
    	$Daily_output->users_id = $req->input('users_id', null);
    	$Daily_output->shift = $req->input('shift', null);
    	$Daily_output->tanggal = $req->input('tanggal', null);

        if(!$Daily_output->minute){$Daily_output->minute = 60; } //set default value for minute
        if(!$Daily_output->target_sop){$Daily_output->target_sop = 0; }
        if(!$Daily_output->osc_output){$Daily_output->osc_output = 0; }
        if(!$Daily_output->plus_minus){$Daily_output->plus_minus = 0; }
        if(!$Daily_output->lost_hour){$Daily_output->lost_hour = 0; }
        if(!$Daily_output->board_delay){$Daily_output->board_delay = 0; }
        if(!$Daily_output->part_delay){$Daily_output->part_delay = 0; }
        if(!$Daily_output->eqp_trouble){$Daily_output->eqp_trouble = 0; }
        if(!$Daily_output->quality_problem_delay){$Daily_output->quality_problem_delay = 0; }
        if(!$Daily_output->bal_problem){$Daily_output->bal_problem = 0; }
        if(!$Daily_output->others){$Daily_output->others = 0; }
        if(!$Daily_output->support){$Daily_output->support = 0; }
        if(!$Daily_output->change_model){$Daily_output->change_model = 0; }
        if(!$Daily_output->users_id){$Daily_output->users_id = null; }

    	$Daily_output->save();

    	return [
    		'_meta'=>[
    			'status'=> "SUCCESS",
    			'userMessage'=> "Data saved",
    			'count'=>count($Daily_output)
    		],
            'data'=>$Daily_output
    	];		
    }

    public function delete(Request $req)
    {
    	# code...
        $currentUser = JWTAuth::parseToken()->authenticate();

    	$Daily_output = Daily_output::find($req->id);
        if( !empty($Daily_output) )
        {
            $Daily_output->delete();
            return [ 
	    		'_meta'=>[
	    			'status'=> "SUCCESS",
	    			'userMessage'=> "Data deleted",
	    			'count'=>count($Daily_output)
	    		]
	    	];
        }
        else
        {
            return [ 
	    		'_meta'=>[
	    			'status'=> "FAILED",
	    			'userMessage'=> "Data not found",
	    			'count'=>count($Daily_output)
	    		]
	    	];
        }
    }

    public function update(Request $req)
    {
    	# code...
        $currentUser = JWTAuth::parseToken()->authenticate();        

        $Daily_output = Daily_output::find($req->id);
        
        $Daily_output->line_name = $req->line_name;
    	$Daily_output->time = $req->time;
    	$Daily_output->minute = $req->minute;
    	$Daily_output->target_sop = $req->target_sop;
    	$Daily_output->osc_output = $req->osc_output;
    	$Daily_output->plus_minus = $req->plus_minus;
    	$Daily_output->lost_hour = $req->lost_hour;
        //new column
        if( !empty($req->board_delay) )
        {$Daily_output->board_delay = $req->board_delay;}
        else {$Daily_output->board_delay = 0;}
        
        if( !empty($req->part_delay) )
        {$Daily_output->part_delay = $req->part_delay;}
        else{$Daily_output->part_delay = 0;}

        if( !empty($req->eqp_trouble) )
        {$Daily_output->eqp_trouble = $req->eqp_trouble;}
        else{$Daily_output->eqp_trouble = 0;}

        if( !empty($req->quality_problem_delay) )
        {$Daily_output->quality_problem_delay = $req->quality_problem_delay;}
        else{$Daily_output->quality_problem_delay = 0;}

        if( !empty($req->bal_problem) )
        {$Daily_output->bal_problem = $req->bal_problem;}
        else{$Daily_output->bal_problem = 0;}
        
        if( !empty($req->others) )
        {$Daily_output->others = $req->others;} 
        else{$Daily_output->others = 0;}
        
        if( !empty($req->support) )
        {$Daily_output->support = $req->support;}
        else{$Daily_output->support = 0;}

        
        if( !empty($req->change_model) )
        {$Daily_output->change_model = $req->change_model;}
        else{$Daily_output->change_model = 0;}

        //end new column
    	$Daily_output->delay_type = $req->delay_type;
    	$Daily_output->problem = $req->problem;
    	$Daily_output->dic = $req->dic;
    	$Daily_output->action = $req->action;
    	$Daily_output->users_id = $req->users_id;
    	$Daily_output->shift = $req->shift;
    	$Daily_output->tanggal = $req->tanggal;

        //error handler
            if(!$Daily_output->minute){$Daily_output->minute = 60; }
            if(!$Daily_output->target_sop){$Daily_output->target_sop = 0; }
            if(!$Daily_output->osc_output){$Daily_output->osc_output = 0; }
            if(!$Daily_output->plus_minus){$Daily_output->plus_minus = 0; }
            if(!$Daily_output->lost_hour){$Daily_output->lost_hour = 0; }
            if(!$Daily_output->board_delay){$Daily_output->board_delay = 0; }
            if(!$Daily_output->part_delay){$Daily_output->part_delay = 0; }
            if(!$Daily_output->eqp_trouble){$Daily_output->eqp_trouble = 0; }
            if(!$Daily_output->quality_problem_delay){$Daily_output->quality_problem_delay = 0; }
            if(!$Daily_output->bal_problem){$Daily_output->bal_problem = 0; }
            if(!$Daily_output->others){$Daily_output->others = 0; }
            if(!$Daily_output->support){$Daily_output->support = 0; }
            if(!$Daily_output->change_model){$Daily_output->change_model = 0; }
            if(!$Daily_output->users_id){$Daily_output->users_id = null; }
        //END ERROR HANDLER

        if ( $Daily_output != null ){
            $Daily_output->save();
            return [ 
                '_meta'=>[
                    'status'=> "SUCCESS",
                    'userMessage'=> "Data updated",
                    'count'=> 1 //count($Daily_output)
                ],
                 'data'=>$Daily_output
            ];
        }
    }

    public function getAllLinePerDay(Request $request){

        $daily_outputs = Daily_output::select(DB::raw(
            'tanggal,
             shift,
             line_name,
             sum(target_sop) as target_sop,
             sum(osc_output) as osc_output,
             sum(plus_minus) as plus_minus,
             sum(lost_hour) as lost_hour,
             sum(board_delay) as board_delay,
             sum(part_delay) as part_delay,
             sum(eqp_trouble) as eqp_trouble,
             sum(quality_problem_delay) as quality_problem_delay,
             sum(bal_problem) as bal_problem,
             sum(others) as others,
             sum(support) as support,
             sum(change_model) as change_model
            '
        ))->groupBy('tanggal')
        ->groupBy('shift')
        ->groupBy('line_name');

        if (isset($request->shift) && $request->shift != null ) {
            $daily_outputs = $daily_outputs->where('shift', '=', $request->shift );
        }

        if (isset($request->tanggal)) {
            $daily_outputs = $daily_outputs->where('tanggal', '=', $request->tanggal );
        }else{
            $request->tanggal = date('Y-m-d');
            $daily_outputs = $daily_outputs->where('tanggal', '=', $request->tanggal );
        }        

        try {
            $message = 'OK';
            $daily_outputs = $daily_outputs
            // ->orderByRaw('case shift when "A" then 1 else 2 end')
            ->orderBy('shift', 'asc')
            ->orderBy('line_name','asc')
            ->get();
        } catch (Exception $e) {
            $message = $e;
        }

        // setting efisiensi & ganti jadi int
        $daily_outputs->each(function($model){
            $model->target_sop = (int) $model->target_sop;
            $model->osc_output = (int) $model->osc_output;
            $model->plus_minus = (int) $model->plus_minus;
            $model->lost_hour = (int) $model->lost_hour;
            
            if ($model->target_sop != 0) {
                $model->efisiensi = round( ($model->osc_output / $model->target_sop) * 100, 2 ) ;
            }else{
                $model->efisiensi = 0;
            }

        });

        return [
            '_meta' => [
                'count' => count($daily_outputs),
                'message' => $message
            ],
            'data' => $daily_outputs
        ];
    }

    public function getAllLinePerMonth(Request $request){
        $daily_outputs = Daily_output::select(DB::raw(
            'MONTH(tanggal) as month,
             line_name,
             sum(target_sop) as target_sop,
             sum(osc_output) as osc_output,
             sum(plus_minus) as plus_minus,
             sum(lost_hour) as lost_hour,
             sum(board_delay) as board_delay,
             sum(part_delay) as part_delay,
             sum(eqp_trouble) as eqp_trouble,
             sum(quality_problem_delay) as quality_problem_delay,
             sum(bal_problem) as bal_problem,
             sum(others) as others,
             sum(support) as support,
             sum(change_model) as change_model
            '
        ))->groupBy('month')
        ->groupBy('line_name')->orderBy('line_name', 'asc');

        if (isset($request->month)) {
            $daily_outputs = $daily_outputs->whereMonth('tanggal', '=', $request->month );
        }else{
            $request->month = date('m');
            $daily_outputs = $daily_outputs->whereMonth('tanggal', '=', $request->month );
        }        

        try {
            $message = 'OK';
            $daily_outputs = $daily_outputs->get();
        } catch (Exception $e) {
            $message = $e;
        }

        // get efisiensi
        $daily_outputs->each(function($model){
            $model->target_sop = (int) $model->target_sop;
            $model->osc_output = (int) $model->osc_output;
            $model->plus_minus = (int) $model->plus_minus;
            $model->lost_hour = (int) $model->lost_hour;
            
            if ($model->target_sop != 0) {
                $model->efisiensi = round( ($model->osc_output / $model->target_sop) * 100, 2 );
            }else{
                $model->efisiensi = 0;
            }

        });

        return [
            '_meta' => [
                'count' => count($daily_outputs),
                'message' => $message
            ],
            'data' => $daily_outputs
        ];
    }
}
