<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use JWTAuth;
use Dingo\Api\Routing\Helpers;
use App\User;
use Carbon\Carbon;
use App\Lost_time;

class LostTimeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $req)
    {
        //
        $lost_time = DB::table('lost_times');

        //kalau get ada parameter start_date, maka yang muncul adalah between start_date s/d start_date->addWeek()

        // parameter tanggal
        if( $req->start_date != null ){

            $lost_time = $lost_time->whereBetween('tanggal', [Carbon::createFromFormat('Y-m-d', $req->start_date ), 
                Carbon::createFromFormat('Y-m-d', $req->start_date )->addWeek() ]  );
        }

        /*parameter tanggal*/
        if( $req->tanggal != null ){

            $lost_time = $lost_time->where('tanggal','=', $req->tanggal ) ;
            // return $req->tanggal;
        }

        if ($req->shift != null) {
            # code...
            $lost_time = $lost_time->where('shift', '=', $req->shift);
        }

        if ($req->line_name != null) {
            # code...
            $lost_time = $lost_time->where('line_name', '=', $req->line_name);
        }

        //pagination
        if ( $req->limit !=null){
            $lost_time = $lost_time->paginate($req->limit);
        }else{
            $lost_time = $lost_time->paginate(15);
        }

        //jika jumlah $lost_time > 0 maka $message = data found, otherwise data not found;
        if (count($lost_time) > 0) {
            $message = 'Data found';
        }else{
            $message = 'Data not found';
        }

        //collect adalah helper laravel untuk array
        $additional_message = collect(['_meta'=> [
            'message'=>$message,
            'count'=> count($lost_time)
        ] ]);
        //adding additional message
        $lost_time = $additional_message->merge($lost_time);
        //$lost_time is object, need to changes to array first!
        $lost_time = $lost_time->toArray();

        //cek kalu $lost_time->data kosong dan parameter2 tsb memenuhi, maka add.
        if( $req->tanggal != null && $req->shift != null && $req->line_name != null && 
            empty($lost_time['data']) )
        {
            //aunthenticate users id
            $currentUser = JWTAuth::parseToken()->authenticate();

            $result = $this->input_data($req->tanggal, $req->shift,$req->line_name, $currentUser->id );
            // return $result;
            $lost_time['data'] = $result;
        }

        return $lost_time;
    }

    public function input_data($tanggal, $shift, $line_name, $users_id ){
        // get parameter,        
        //make variable $time based on shift
        $shiftA = [
            ['id'=>1, 'time'=> '06-07', 'durasi'=> 60, 'jumat'=> 60 ],
            ['id'=>2, 'time'=> '07-08', 'durasi'=> 60, 'jumat'=> 50 ],
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
            ['id'=>2, 'time'=> '07-08', 'durasi'=> 15, 'jumat'=> 15 ],
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
            ['id'=>11, 'time'=> '16-17', 'durasi'=> 60, 'jumat'=> 60],
            ['id'=>12, 'time'=> '17-18', 'durasi'=> 60, 'jumat'=> 50],
            ['id'=>13, 'time'=> '18-19', 'durasi'=> 50, 'jumat'=> 50],
            ['id'=>14, 'time'=> '19-20', 'durasi'=> 60, 'jumat'=> 60],
            ['id'=>15, 'time'=> '20-21', 'durasi'=> 50, 'jumat'=> 50],
            ['id'=>16, 'time'=> '21-22', 'durasi'=> 60, 'jumat'=> 60],
            ['id'=>17, 'time'=> '22-23', 'durasi'=> 25, 'jumat'=> 10],
            ['id'=>18, 'time'=> '23-24', 'durasi'=> 60, 'jumat'=> 50],
            ['id'=>19, 'time'=> '00-01', 'durasi'=> 60, 'jumat'=> 60],
            ['id'=>20, 'time'=> '01-02', 'durasi'=> 5, 'jumat'=> 30]
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
            
            //store to database.
            $Lost_time = new Lost_time;

            $Lost_time->time = $value['time'];
            $Lost_time->users_id = $users_id;
            $Lost_time->tanggal = $tanggal;
            $Lost_time->lost_time = 0; //default value
            $Lost_time->shift = $shift;
            $Lost_time->line_name = $line_name;

            $Lost_time->save();

            $result[] = $Lost_time;
        }

        return $result;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $req)
    {
        $currentUser = JWTAuth::parseToken()->authenticate();
        //return $currentUser;

        $lost_time = new Lost_time;
        //inputan
          $lost_time->line_name = $req->input('line_name', null);
          $lost_time->shift = $req->input('shift', null);
          $lost_time->time = $req->input('time', null);
          $lost_time->problem = $req->input('problem', null);
          $lost_time->lost_time = $req->input('lost_time', null);
          $lost_time->cause = $req->input('cause', null);
          $lost_time->action = $req->input('action', null);
          $lost_time->tanggal = $req->input('tanggal', null);
          $lost_time->followed_by = $req->input('followed_by', null);
          $lost_time->users_id = $req->input('users_id', null);
        //inputan end

        $lost_time->save();

        return [
            '_meta'=>[
                'status'=> "SUCCESS",
                'userMessage'=> "Data saved",
                'count'=>count($lost_time)
            ],
            'data'=>$lost_time
        ];
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $req, $id)
    {
        # code...
        $currentUser = JWTAuth::parseToken()->authenticate();        

        $lost_time = Lost_time::find($req->id);
        if ( $lost_time != null ){ 
            $lost_time->line_name = $req->input('line_name', $lost_time->line_name );
            $lost_time->shift = $req->input('shift', $lost_time->shift);
            $lost_time->time = $req->input('time', $lost_time->time);
            $lost_time->problem = $req->input('problem', $lost_time->problem);
            $lost_time->lost_time = $req->input('lost_time', $lost_time->lost_time);
            $lost_time->cause = $req->input('cause', $lost_time->cause);
            $lost_time->action = $req->input('action', $lost_time->action);
            $lost_time->tanggal = $req->input('tanggal', $lost_time->tanggal);
            $lost_time->followed_by = $req->input('followed_by', $lost_time->followed_by);
            $lost_time->users_id = $req->input('users_id', $lost_time->users_id);
            // return $lost_time;
            $lost_time->save();
            return [ 
                '_meta'=>[
                    'status'=> "SUCCESS",
                    'userMessage'=> "Data updated",
                    'count'=>count($lost_time)
                ],
                 'data'=>$lost_time
            ];
        }else{
            return [ 
                '_meta'=>[
                    'status'=> "FAILED",
                    'userMessage'=> "Data not found",
                    'count'=>count($lost_time)
                ],
                 'data'=>$lost_time
            ];
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
        $currentUser = JWTAuth::parseToken()->authenticate();

        $lost_time = Lost_time::find($id);
        if( !empty($lost_time) )
        {
            $lost_time->delete();
            return [ 
                '_meta'=>[
                    'status'=> "SUCCESS",
                    'userMessage'=> "Data deleted",
                    'count'=>count($lost_time)
                ]
            ];
        }
        else
        {
            return [ 
                '_meta'=>[
                    'status'=> "FAILED",
                    'userMessage'=> "Data not found",
                    'count'=>count($lost_time)
                ]
            ];
        }
    }

    public function getPerLine(Request $request){
        $lost_time = Lost_time::select(DB::raw("line_name,
            sum(lost_time) as lost_time,
            tanggal
            "))->groupBy('line_name')
            ->groupBy('tanggal');

        if (isset($request->tanggal) && $request->tanggal != "" ) {
            $tanggal = $request->tanggal;
        }else{
            $tanggal = date('Y-m-d');
        }

        $lost_time = $lost_time->where('tanggal', $tanggal);
        $lost_time = $lost_time->get();
        
        return [
            'message' => 'OK',
            'tanggal' => $tanggal,
            'count' => count($lost_time),
            'data' => $lost_time
        ];
    }

    public function getPerMonth(Request $request){
        $lost_time = Lost_time::select(DB::raw("
            sum(lost_time) as lost_time,
            (sum(lost_time) / 60) as lost_time_hour,
            tanggal
        "));

        if (isset($request->month) ) {
            $month = $request->month;
        }else{
            $month = date('m');
        }

        if (isset($request->year) && $request->year != "" ) {
            $year = $request->year;
        }else{
            $year = date('Y');
        }

        // $arrayLineName = [19]; //[18,19,20,21,22,23,24,25];

        $lost_time = $lost_time->whereMonth('tanggal', '=', $month );
        $lost_time = $lost_time->whereYear('tanggal', '=', $year );

        // $daily_repair = $daily_repair->whereIn('line_name', $arrayLineName);
        $lost_time = $lost_time->orderBy('tanggal')
        ->groupBy('tanggal')
        ->get();

        return [
            'message' => 'OK',
            'month' => $month,
            'year' =>$year, 
            'data'=>    $lost_time
        ];
    }
}
