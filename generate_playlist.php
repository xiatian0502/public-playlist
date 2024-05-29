     <?php

     $playlistIds = ['PLQWMqkNuwweK2NUFEex3Jked5lBWcUIJc&index=6', 'PLNXJ_YC1PDA1L6H_ec0pn25QkDdK_8KrB']; // 直接在脚本中定义播放列表ID
     $maxResults = 20;

     $API_key = 'AIzaSyAONZd3f8TN6QZS39WCeddl7YqP1TdhkkQ'; // 你的API_KEY

     $yt_dlp_path = "/usr/local/bin/yt-dlp";
     $yt_dlp_version = shell_exec("$yt_dlp_path --version");
     file_put_contents('yt_dlp_version.txt', $yt_dlp_version);

     $m3uFilePath = 'youtube.m3u';
     file_put_contents($m3uFilePath, "#EXTM3U" . PHP_EOL);

     foreach ($playlistIds as $playlistId) {
         // 使用播放列表ID获取视频列表
         $videoList = json_decode(file_get_contents('https://www.googleapis.com/youtube/v3/playlistItems?part=snippet&maxResults=' . $maxResults . '&playlistId=' . $playlistId . '&key=' . $API_key . ''), true);

         if (!$videoList || !isset($videoList['items'])) {
             file_put_contents($m3uFilePath, "#播放列表" . $playlistId . "未找到" . PHP_EOL, FILE_APPEND);
             continue;
         }

         foreach ($videoList['items'] as $item) {
             $youtubeUrl = 'https://www.youtube.com/watch?v=' . $item['snippet']['resourceId']['videoId'];
             
             // 使用 yt-dlp 获取流媒体链接并返回格式为 m3u8
             $command = "$yt_dlp_path -f best --get-url --no-playlist --no-warnings --force-generic-extractor --user-agent 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0' --youtube-skip-dash-manifest " . escapeshellarg($youtubeUrl);
             $streamUrl = shell_exec($command);

             // 调试输出
             file_put_contents('command_output.txt', "Command: $command\nOutput: $streamUrl\n");

             // 如果能提取到 .m3u8 链接，优先使用
             if (strpos($streamUrl, '.m3u8') !== false) {
                 $streamUrl = trim($streamUrl);
             } else {
                 $streamUrl = $youtubeUrl;  // 否则回退到 YouTube 视频链接
             }
             
             file_put_contents($m3uFilePath, "#EXTINF:-1," . $item['snippet']['title'] . PHP_EOL, FILE_APPEND);
             file_put_contents($m3uFilePath, $streamUrl . PHP_EOL, FILE_APPEND);
         }
     }
