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
        $date = now()->toDateString();

        // 当日の勤務記録を取得
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $date)
            ->first();

        $data = [
            'startButton' => false,
            'endButton' => false,
            'startBreakButton' => false,
            'endBreakButton' => false,
        ];

        if (!$attendance || $attendance->start_time && $attendance->end_time) {
            // 勤務記録が存在しないか、既に勤務が終了している場合
            $data['startButton'] = true;
        } elseif ($attendance->start_Break && !$attendance->end_Break) {
            // 休憩中の場合
            $data['endBreakButton'] = true;
        } elseif ($attendance->start_time && !$attendance->end_time) {
            // 勤務中の場合
            $data['endButton'] = true;
            $data['startBreakButton'] = true;
        }
        return view('stamp', $data);
    }

    // 打刻関連

    public function startTime()
    {
        $user = Auth::user();
        $date = now()->toDateString();
        // 当日の勤務記録を取得または新しいレコードを作成
        $attendance = Attendance::firstOrNew([
            'user_id' => $user->id,
            'date' => $date,
        ]);
        // 勤務終了時間が空であることを確認
        if (!$attendance->end_time) {
            $attendance->start_time = now();
            $attendance->save();
        }
        return redirect()->route('stamp');
    }

    public function endTime()
    {
        $user = Auth::user();
        $date = now()->toDateString();
        // 当日の勤務記録を取得または新しいレコードを作成
        $attendance = Attendance::firstOrNew([
            'user_id' => $user->id,
            'date' => $date,
        ]);
        // 勤務終了時間が空であることを確認
        if (!$attendance->end_time) {
            $attendance->end_time = now();
            $attendance->save();
        }
        return redirect()->route('stamp');
    }

    public function startBreak()
    {
        $user = Auth::user();
        $date = now()->toDateString();
        // 当日の勤務記録を取得または新しいレコードを作成
        $attendance = Attendance::firstOrNew([
            'user_id' => $user->id,
            'date' => $date,
        ]);
        if (!$attendance->start_Break) {
            $attendance->start_Break = now();
            $attendance->save();
        }
        return redirect()->route('stamp');
    }

    public function endBreak()
    {
        $user = Auth::user();
        $date = now()->toDateString();
        // 当日の勤務記録を取得または新しいレコードを作成
        $attendance = Attendance::firstOrNew([
            'user_id' => $user->id,
            'date' => $date,
        ]);
        if (!$attendance->end_Break) {
            $attendance->end_Break = now();
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
            $startBreak = Carbon::parse($datebase->start_Break);
            $endBreak = Carbon::parse($datebase->end_Break);
            $datebase->Break_time = $endBreak->diff($startBreak)->format('%H:%I:%S');

            $startTime = Carbon::parse($datebase->start_time);
            $endTime = Carbon::parse($datebase->end_time);
            $BreakTime = Carbon::parse($datebase->Break_time);
            $workTime = $endTime->diff(($startTime)->sub($endBreak->diff($startBreak)));
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
            $startBreak = Carbon::parse($datebase->start_Break);
            $endBreak = Carbon::parse($datebase->end_Break);
            $datebase->Break_time = $endBreak->diff($startBreak)->format('%H:%I:%S');

            $startTime = Carbon::parse($datebase->start_time);
            $endTime = Carbon::parse($datebase->end_time);
            $BreakTime = Carbon::parse($datebase->Break_time);
            $workTime = $endTime->diff(($startTime)->sub($endBreak->diff($startBreak)));
            $datebase->work_time = $workTime->format('%H:%I:%S');
        }

        return view('attendance', compact('today', 'prevDate', 'nextDate', 'attendances', 'datebases'));
    }

    // ログアウト関連

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
