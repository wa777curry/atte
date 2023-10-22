<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Rest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function __construct() {
        $this->middleware('auth');
    }

    //　打刻ページ関連

    public function index() {
        $user = Auth::user();
        $attendance = Attendance::where('user_id', $user->id)
            ->where('end_time', null)
            ->latest()
            ->first();

        $data = [
            'startButton' => true,
            'endButton' => false,
            'startRestButton' => false,
            'endRestButton' => false,
        ];

        if ($attendance) {
            $data['startButton'] = false;
            if (!$attendance->end_time) {
                $data['endButton'] = true;
                $data['startRestButton'] = true;
            } elseif (!$attendance->end_Rest) {
                $data['endRestButton'] = true;
            }
        }

        return view('stamp', $data);
    }


    // 打刻関連

    public function startTime() {
        $user = Auth::user();
        $now = Carbon::now();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'start_time' => $now,
        ]);

        return redirect()->route('stamp');
    }

    public function endTime() {
        $latestAttendance = Attendance::orderBy('created_at', 'desc')->first();

        if ($latestAttendance) {
            $latestAttendance->update([
                'end_time' => now(),
            ]);
        }

        return redirect()->route('stamp');
    }


        /*
    public function startRest()
    {
        $user = Auth::user();
        $date = now()->toDateString();
        // 当日の勤務記録を取得または新しいレコードを作成
        $attendance = Attendance::firstOrNew([
            'user_id' => $user->id,
            'date' => $date,
        ]);
        if (!$attendance->start_Rest) {
            $attendance->start_Rest = now();
            $attendance->save();
        }
        return redirect()->route('stamp');
    }

    public function endRest()
    {
        $user = Auth::user();
        $date = now()->toDateString();
        // 当日の勤務記録を取得または新しいレコードを作成
        $attendance = Attendance::firstOrNew([
            'user_id' => $user->id,
            'date' => $date,
        ]);
        if (!$attendance->end_Rest) {
            $attendance->end_Rest = now();
            $attendance->save();
        }
        return redirect()->route('stamp');
    }



    // 日付一覧関連

    public function attendance(Request $request)
    {
        $today = Carbon::createFromFormat('Y-m-d', $request->input('date', now()->toDateString()));
        $prevDate = $today->copy()->subDay();
        $nextDate = $today->copy()->addDay();
        $attendances = Attendance::getByDate($today);

        $datebases = Attendance::whereDate('date', $today)->paginate(5);

        foreach ($datebases as $datebase) {
            $datebase->start_time = Carbon::parse($datebase->start_time)->format('H:i:s');
            $datebase->end_time = Carbon::parse($datebase->end_time)->format('H:i:s');
            $startRest = Carbon::parse($datebase->start_Rest);
            $endRest = Carbon::parse($datebase->end_Rest);
            $datebase->rest_time = $endRest->diff($startRest)->format('%H:%I:%S');

            $startTime = Carbon::parse($datebase->start_time);
            $endTime = Carbon::parse($datebase->end_time);
            $restTime = Carbon::parse($datebase->rest_time);
            $workTime = $endTime->diff(($startTime)->sub($endRest->diff($startRest)));
            $datebase->work_time = $workTime->format('%H:%I:%S');
        }

        Paginator::useBootstrap();

        return view('attendance', compact('today', 'attendances', 'prevDate', 'nextDate', 'datebases'));
    }

    public function attendanceDate($date)
    {
        $date = Carbon::createFromFormat('Y-m-d', $date);
        $prevDate = $date->copy()->subDay();
        $nextDate = $date->copy()->addDay();
        $attendances = Attendance::getByDate($date);
        $today = $date;

        $datebases = Attendance::whereDate('date', $date)->get();

        foreach ($datebases as $datebase) {
            $datebase->start_time = Carbon::parse($datebase->start_time)->format('H:i:s');
            $datebase->end_time = Carbon::parse($datebase->end_time)->format('H:i:s');
            $startRest = Carbon::parse($datebase->start_Rest);
            $endRest = Carbon::parse($datebase->end_Rest);
            $datebase->rest_time = $endRest->diff($startRest)->format('%H:%I:%S');

            $startTime = Carbon::parse($datebase->start_time);
            $endTime = Carbon::parse($datebase->end_time);
            $restTime = Carbon::parse($datebase->rest_time);
            $workTime = $endTime->diff(($startTime)->sub($endRest->diff($startRest)));
            $datebase->work_time = $workTime->format('%H:%I:%S');
        }

        return view('attendance', compact('today', 'prevDate', 'nextDate', 'attendances', 'datebases'));
    }
    */

    // ログアウト関連

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}