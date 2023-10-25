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
    public function __construct()
    {
        $this->middleware('auth');
    }

    //　打刻ページ関連

    public function index()
    {
        $user = Auth::user();
        $attendance = Attendance::where('user_id', $user->id)
            ->where('end_time', null)
            ->latest()
            ->first();

        $startTimeButton = true;
        $endTimeButton = false;
        $startRestButton = false;
        $endRestButton = false;

        if ($attendance) {
            $startTimeButton = false;
            if ($attendance->start_rest) {
                $endRestButton = true;
            } elseif ($attendance->end_rest) {
                $endTimeButton = true;
                $startRestButton = true;
            } elseif (!$attendance->end_time) {
                $endTimeButton = true;
                $startRestButton = true;
            }
        }

        $data = [
            'startTimeButton' => $startTimeButton,
            'endTimeButton' => $endTimeButton,
            'startRestButton' => $startRestButton,
            'endRestButton' => $endRestButton,
        ];

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


    /*
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
            $startRest = Carbon::parse($datebase->start_rest);
            $endRest = Carbon::parse($datebase->end_rest);
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
            $startRest = Carbon::parse($datebase->start_rest);
            $endRest = Carbon::parse($datebase->end_rest);
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
