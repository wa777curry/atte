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
use Carbon\CarbonInterval;

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
        $date = Carbon::createFromFormat('Y-m-d', $request->input('date', now()->toDateString())); // 日付の表示
        $prevDate = $date->copy()->subDay(); // 前日日付の取得
        $nextDate = $date->copy()->addDay(); // 翌日日付の取得

        // DBからのデータ取得
        $datebases = Attendance::with('rests')
            ->where(function ($query) use ($date) {
                $query->whereDate('start_time', $date) // 当日勤務開始あり
                ->orWhereDate('end_time', $date) // 当日勤務終了あり
                ->orWhere(function ($query) use ($date) {
                    $query->where('start_time', '<', $date->endOfDay()) // 前日以前勤務開始あり
                    ->where('end_time', '>', $date->startOfDay()); // 翌日以降勤務終了あり
                });
            })
            ->get();

        $startWorkTime = null; // 初期値
        $endWorkTime = null; // 初期値

        $datebases = $datebases->map(function ($datebase) use ($request) {
            $startWork = Carbon::parse($datebase->start_time); // 勤務開始の情報
            $endWork = Carbon::parse($datebase->end_time); // 勤務終了の情報
            $pageDate = Carbon::parse($request->input('date', now()->toDateString())); // 基準日

            if ($startWork->isSameDay($pageDate) && $endWork->isSameDay($pageDate)) {
                // 今日〜今日の勤務
                $startWorkTime = $startWork->format('H:i:s');
                $endWorkTime = $endWork->format('H:i:s');
            } elseif (!$startWork->isSameDay($pageDate) && !$endWork->isSameDay($pageDate)) {
                // 前日以前〜翌日以降の勤務
                $startWorkTime = now()->startOfDay()->format('H:i:s'); // 今日の最初の時間
                $endWorkTime = now()->endOfDay()->format('H:i:s'); // 今日の最後の時間
            } elseif ($startWork->isSameDay($pageDate) && !$endWork->isSameDay($pageDate)) {
                    // 今日〜翌日以降の勤務
                    $startWorkTime = $startWork->format('H:i:s');
                    $endWorkTime = now()->endOfDay()->format('H:i:s'); // 今日の最後の時間
            } elseif (!$startWork->isSameDay($pageDate) && $endWork->isSameDay($pageDate)) {
                    // 前日以前〜今日の勤務
                    $startWorkTime = now()->startOfDay()->format('H:i:s'); // 今日の最初の時間
                    $endWorkTime = $endWork->format('H:i:s');
            }

            $datebase->startWorkTime = $startWorkTime;
            $datebase->endWorkTime = $endWorkTime;

            return $datebase;
        });

        return view('attendance', compact('date', 'datebases', 'prevDate', 'nextDate'));
    }
}
        /*
        $datebases = Attendance::with('rests')
            ->whereDate('start_time', $date) // DBから勤務開始時間の引用
            ->whereDate('end_time', $date) // DBから勤務終了時間の引用
            ->get();

        $datebases = $datebases->map(
            function ($datebase) use (&$startWork, &$endWork, $date) {
                $startDate = Carbon::parse($datebase->start_time);
                $endDate = Carbon::parse($datebase->end_time);

                if ($startDate->isSameDay($date) && $endDate->isSameDay($date)) {
                    // 今日〜今日の勤務
                    $startWork = $startDate;
                    $endWork = $endDate;
                } elseif (!$startDate->isSameDay($date) && !$endDate->isSameDay($date)) {
                    // 前日以前〜翌日以降の勤務
                    $startWork = now()->startOfDay(); // 今日の最初の時間
                    $endWork = now()->endOfDay(); // 今日の最後の時
                } else {
                    if ($startDate->isSameDay($date) && !$endDate->isSameDay($date)) {
                        // 今日〜翌日以降の勤務
                        $startWork = $startDate;
                        $endWork = now()->endOfDay(); // 今日の最後の時
                    } elseif (!$startDate->isSameDay($date) && $endDate->isSameDay($date)) {
                        // 前日以前〜今日の勤務
                        $startWork = now()->startOfDay(); // 今日の最初の時間
                        $endWork = $endDate;
                    }
                }
                return $datebase;
            }
        );

        return view('attendance', compact('date', 'datebases', 'prevDate', 'nextDate', 'startWork', 'endWork'));

    }
}

            /*
        $datebases = $datebases->map(function ($datebase) {
            $datebase->start_time = Carbon::parse($datebase->start_time);
            $datebase->end_time = Carbon::parse($datebase->end_time);

            if ($datebase->rests->isNotEmpty()) {
                $totalRestTimeInSeconds = 0;

                // 休憩時間の計算
                foreach ($datebase->rests as $rest) {
                    $startRest = Carbon::parse($rest->start_rest);
                    $endRest = Carbon::parse($rest->end_rest);
                    $restTimeInSeconds = $endRest->diffInSeconds($startRest); // 秒で計算
                    $totalRestTimeInSeconds += $restTimeInSeconds;
                }
                $totalRestTime = gmdate('H:i:s', $totalRestTimeInSeconds);
                $datebase->rest_time = $totalRestTime;

                // 勤務時間の計算（休憩あり）
                $startWork = Carbon::parse($datebase->start_time);
                    // ３パターンで分岐する（同日、
                $endWork = Carbon::parse($datebase->end_time);
                    // ３パターンで分岐する

                if ($startWork->isSameDay($endWork)) {
                    // 同じ日に勤務が開始と終了した場合
                    $workTime = $endWork->diffInSeconds($startWork);
                    $totalWorkTimeInSeconds = $workTime - $totalRestTimeInSeconds;
                    $totalWorkTime = CarbonInterval::seconds($totalWorkTimeInSeconds);
                    $totalWorkTime = gmdate('H:i:s', $totalWorkTimeInSeconds);
                    $datebase->work_time = $totalWorkTime;
                } else {
                    // 日付が変わった場合
                    $workTimeToday = $startWork->copy()->endOfDay()->diffInSeconds($startWork);
                    $workTimeTomorrow = $endWork->copy()->startOfDay()->diffInSeconds($endWork);
                    $totalWorkTimeInSeconds = $workTimeToday + $workTimeTomorrow - $totalRestTimeInSeconds;
                    $totalWorkTime = CarbonInterval::seconds($totalWorkTimeInSeconds);
                    $totalWorkTime = gmdate('H:i:s', $totalWorkTimeInSeconds);
                    $datebase->work_time = $totalWorkTime;
                }

            } else {
                $datebase->rest_time = "休憩なし";
                $startWork = Carbon::parse($datebase->start_time);
                $endWork = Carbon::parse($datebase->end_time);
                $workTime = $endWork->diff($startWork);
                $datebase->work_time = $workTime->format('%H:%I:%S');
            }
                return $datebase;
            });

        return view('attendance', compact('date', 'datebases', 'prevDate', 'nextDate'));
    }
}