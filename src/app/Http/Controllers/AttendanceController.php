<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Rest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AttendanceController extends Controller {
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
        // データ処理のまとめ
        $data = $this->createdata($request);

        return view('attendance', $data);
    }

    private function createdata(Request $request) {
        $date = Carbon::createFromFormat('Y-m-d', $request->input('date', now()->toDateString())); // 日付の表示
        $prevDate = $date->copy()->subDay(); // 前日日付の取得
        $nextDate = $date->copy()->addDay(); // 翌日日付の取得

        // DBから勤務データ取得
        $databases = Attendance::with('rests')
        ->where(function ($query) use ($date) {
            $query->whereDate('start_time', $date) // 当日勤務開始あり
                ->orWhereDate('end_time', $date) // 当日勤務終了あり
                ->orWhere(function ($query) use ($date) {
                    $query->where('start_time', '<', $date->endOfDay()) // 前日以前勤務開始あり
                        ->where('end_time', '>', $date->startOfDay()); // 翌日以降勤務終了あり
                });
        })
            ->get();

        $startWorkTime = null; // 勤務開始時間の初期値
        $endWorkTime = null; // 勤務終了時間の初期値

        $databases = $databases->map(function ($database) use ($request) {
            $pageDate = Carbon::parse($request->input('date', now()->toDateString())); // 基準日
            $startWork = Carbon::parse($database->start_time); // 勤務開始の情報
            $endWork = Carbon::parse($database->end_time); // 勤務終了の情報

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

            $database->startWorkTime = $startWorkTime;
            $database->endWorkTime = $endWorkTime;

            // 休憩時間の計算
            if ($database->rests->isNotEmpty()) {
                $totalRestTimeInSeconds = 0; // 休憩時間の初期値
                $totalWorkTimeInSeconds = 0; // 勤務合計時間の初期値

                foreach ($database->rests as $rest) {
                    $startRest = Carbon::parse($rest->start_rest); // 休憩開始の情報
                    $endRest = Carbon::parse($rest->end_rest); // 休憩終了の情報

                    // 休憩時間、勤務時間の計算
                    if ($startRest->isSameDay($pageDate) && $endRest->isSameDay($pageDate)) {
                        // 今日〜今日の休憩
                        $totalRestTimeInSeconds += $endRest->diffInSeconds($startRest);
                        $totalWorkTimeInSeconds = Carbon::parse($endWorkTime)->diffInSeconds(Carbon::parse($startWorkTime)) - $totalRestTimeInSeconds;
                    } elseif (!$startRest->isSameDay($pageDate) && !$endRest->isSameDay($pageDate)) {
                        // 前日以前〜翌日以降の休憩
                        $totalRestTimeInSeconds += now()->endOfDay()->diffInSeconds(now()->startOfDay());
                        $totalWorkTimeInSeconds = Carbon::parse($endWorkTime)->diffInSeconds(Carbon::parse($startWorkTime)) - $totalRestTimeInSeconds;
                    } elseif ($startRest->isSameDay($pageDate) && !$endRest->isSameDay($pageDate)) {
                        // 今日〜翌日以降の休憩
                        $totalRestTimeInSeconds += now()->endOfDay()->diffInSeconds($startRest);
                        $totalWorkTimeInSeconds = Carbon::parse($endWorkTime)->diffInSeconds(Carbon::parse($startWorkTime)) - $totalRestTimeInSeconds;
                    } elseif (!$startRest->isSameDay($pageDate) && $endRest->isSameDay($pageDate)) {
                        // 前日以前〜今日の休憩
                        $totalRestTimeInSeconds += $endRest->diffInSeconds(now()->startOfDay());
                        $totalWorkTimeInSeconds = Carbon::parse($endWorkTime)->diffInSeconds(Carbon::parse($startWorkTime)) - $totalRestTimeInSeconds;
                    }
                }
                $totalRestTime = gmdate('H:i:s', $totalRestTimeInSeconds); // 秒から時間への表記変更
                $totalWorkTime = gmdate('H:i:s', $totalWorkTimeInSeconds); // 秒から時間への表記変更
                $database->totalRestTime = $totalRestTime;
                $database->totalWorkTime = $totalWorkTime;
            } else {
                $database->totalRestTime = "休憩なし";
                $totalWorkTimeInSeconds = Carbon::parse($endWorkTime)->diffInSeconds(Carbon::parse($startWorkTime));
                $totalWorkTime = gmdate('H:i:s', $totalWorkTimeInSeconds); // 秒から時間への表記変更
                $database->totalWorkTime = $totalWorkTime;
            }

            return $database;
        });

        $perPage = 5; // 画面の表示件数
        $currentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage() ?: 1; // 現在のページ番号
        $items = $databases->forPage($currentPage, $perPage)->all(); // 現在のページの件数取得
        $totalItems = count($databases);

        $path = $request->url('/attendance');
        $databasesCollection = new \Illuminate\Pagination\LengthAwarePaginator($items, $totalItems, $perPage, $currentPage);
        $databasesCollection->withPath(route('attendance', ['date' => $date->toDateString()]));

        return compact('date', 'databases', 'prevDate', 'nextDate', 'databasesCollection');
    }
}