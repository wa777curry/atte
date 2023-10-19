// JavaScriptで今日の日付を取得
var currentDate = new Date();

// 前日の日付を計算
var prevDate = new Date(currentDate);
prevDate.setDate(currentDate.getDate() - 1);

// 翌日の日付を計算
var nextDate = new Date(currentDate);
nextDate.setDate(currentDate.getDate() + 1);

// 日付を適切なフォーマットに変換
var formattedCurrentDate = currentDate.toISOString().split('T')[0];
var formattedPrevDate = prevDate.toISOString().split('T')[0];
var formattedNextDate = nextDate.toISOString().split('T')[0];

// 前日へのリンク
var prevLink = document.getElementById('prev-link');
prevLink.href = "/attendance/" + formattedPrevDate;

// 翌日へのリンク
var nextLink = document.getElementById('next-link');
nextLink.href = "/attendance/" + formattedNextDate;
