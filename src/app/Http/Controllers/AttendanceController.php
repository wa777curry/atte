<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Rest;
use App\Models\User;
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
        if ($attendance) {
            $rest = Rest::where('attendance_id', $attendance->id)
            ->where('end_rest', null)
            ->latest()
            ->first();
        }

        $startTimeButton = !$attendance || $attendance->end_time;
        $endTimeButton = $attendance && !$attendance->end_time && (!$rest || $rest->end_rest);
        $startRestButton = $attendance && !$attendance->end_time && (!$rest || $rest->end_rest);
        $endRestButton = $attendance && !$attendance->end_time && $rest && !$rest->end_rest;

        return view('stamp', compact('startTimeButton', 'endTimeButton', 'startRestButton', 'endRestButton'));
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

    public function startRest() {
        $attendance = Attendance::orderBy('created_at', 'desc')->first();
        $now = Carbon::now();

        $rest = Rest::create([
            'attendance_id' => $attendance->id,
            'start_rest' => $now,
        ]);

        return redirect()->route('stamp');
    }

    public function endRest() {
        $latestAttendance = Attendance::orderBy('created_at', 'desc')->first();

        if ($latestAttendance) {
            $latestRest = $latestAttendance->rests()->where('end_rest', null)->first();

            if ($latestRest) {
                $latestRest->update([
                    'end_rest' => now(),
                ]);
            }
        }

        return redirect()->route('stamp');
    }


    // 日付一覧関連

    public function attendance(Request $request) {
        $date = Carbon::createFromFormat('Y-m-d', $request->input('date', now()->toDateString()));

        $prevDate = $date->copy()->subDay();
        $nextDate = $date->copy()->addDay();

        $datebases = Attendance::with('rests')->whereDate('start_time',$date)->get();

        $datebases = $datebases->map(function ($datebase) {
            $datebase->start_time = Carbon::parse($datebase->start_time)->format('H:i:s');
            $datebase->end_time = Carbon::parse($datebase->end_time)->format('H:i:s');

            if ($datebase->rests->isNotEmpty()) {
                $totalRestTimeInSeconds = 0;

                foreach ($datebase->rests as $rest) {
                    $startRest = Carbon::parse($rest->start_rest);
                    $endRest = Carbon::parse($rest->end_rest);
                    $restTimeInSeconds = $endRest->diffInSeconds($startRest); // 秒で計算
                    $totalRestTimeInSeconds += $restTimeInSeconds;
                }
                $totalRestTime = gmdate('H:i:s', $totalRestTimeInSeconds);
                $datebase->rest_time = $totalRestTime;


            } else {
                $datebase->rest_time = "休憩なし";
            }
                return $datebase;
            });





        return view('attendance', compact('date', 'datebases'));
    }

    /*
    public function attendanceDate($date) {
        $date = Carbon::createFromFormat('Y-m-d', $date);
        $prevDate = $date->copy()->subDay();
        $nextDate = $date->copy()->addDay();

        $workRecords = Attendance::whereDate('start_time', $date)->get();
        $restRecords = Rest::whereDate('start_rest', $date)->get;

        $datebase->start_time = Carbon::parse($datebase->start_time)->format('H:i:s');
        $datebase->end_time = Carbon::parse($datebase->end_time)->format('H:i:s');
        $startRest = Carbon::parse($datebase->start_rest);
        $endRest = Carbon::parse($datebase->end_rest);
        $datebase->rest_time = $endRest->diff($startRest)->format('%H:%I:%S');

        $startTime = Carbon::parse($datebase->start_time);
        $endTime = Carbon::parse($datebase->end_time);
        $restTime = Carbon::parse($datebase->rest_time);
        $workTime = $endTime->diff($startTime->sub($endRest->diff($startRest)));
        $datebase->work_time = $workTime->format('%H:%I:%S');

        return view('attendance', compact('date', 'prevDate', 'nextDate', 'workRecords'));
    }
    */

    // ログアウト関連

    public function logout(Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}