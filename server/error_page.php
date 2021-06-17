<?php
function show_error_page($error=null, $error_message=null, $error_message_advanced=null){
    global $tools, $webpack_manifest_path;
    if (!headers_sent()) {
        http_response_code($error);
    }
    $error = !is_null($error) ? $error : (isset($_GET["error"]) ? $_GET["error"] : 404);
    if(is_null($error_message)){
        switch ($error){
            case 404:
                $error_message = "Page not found";
                $error_message_advanced = "Please check the url";
                break;

            case 405:
                $error_message = "Method not allowed";
                $error_message_advanced = "";
                break;

            case 500:
                $error_message = "Server error";
                $error_message_advanced = "Please retry later";
                break;
        }
    }
    $main_script_url = null;
    $game_script_url = null;
    try{
        $webpack_manifest_path = isset($webpack_manifest_path) ? $webpack_manifest_path : realpath("resources/dist/assets-manifest.json");
        if(!empty($webpack_manifest_path)){
            $webpack_manifest = json_decode(
                file_get_contents($webpack_manifest_path),
                true
            );
            $main_script_url = "resources/dist/".$webpack_manifest["main.js"]["src"];
            $game_script_url = "resources/dist/".$webpack_manifest["games.js"]["src"];
        }
    } catch(\Exception $e) {
    }

    $error_templates = [
        <<<EOT
    <div id="error">
        <div id="box"></div>
        <h3>ERROR $error</h3>
        <p>$error_message</p>
        <p>$error_message_advanced</p>
    </div>
    <style>
    body,html{height:100%}body{display:grid;width:100%;font-family:Inconsolata,monospace}body div#error{position:relative;margin:auto;padding:20px;z-index:2}body div#error div#box{position:absolute;top:0;left:0;width:100%;height:100%;border:1px solid #000}body div#error div#box:after,body div#error div#box:before{content:'';position:absolute;top:0;left:0;width:100%;height:100%;box-shadow:inset 0 0 0 1px #000;mix-blend-mode:multiply;animation:dance 2s infinite steps(1)}body div#error div#box:before{clip-path:polygon(0 0,65% 0,35% 100%,0 100%);box-shadow:inset 0 0 0 1px currentColor;color:#f0f}body div#error div#box:after{clip-path:polygon(65% 0,100% 0,100% 100%,35% 100%);animation-duration:.5s;animation-direction:alternate;box-shadow:inset 0 0 0 1px currentColor;color:#0ff}body div#error h3{position:relative;font-size:5vw;font-weight:700;text-transform:uppercase;animation:blink 1.3s infinite steps(1)}body div#error h3:after,body div#error h3:before{content:'ERROR $error';position:absolute;top:-1px;left:0;mix-blend-mode:soft-light;animation:dance 2s infinite steps(2)}body div#error h3:before{clip-path:polygon(0 0,100% 0,100% 50%,0 50%);color:#f0f;animation:shiftright 2s steps(2) infinite}body div#error h3:after{clip-path:polygon(0 100%,100% 100%,100% 50%,0 50%);color:#0ff;animation:shiftleft 2s steps(2) infinite}body div#error p{position:relative;margin-bottom:8px}body div#error p span{position:relative;display:inline-block;font-weight:700;color:#000;animation:blink 3s steps(1) infinite}body div#error p span:after,body div#error p span:before{content:'unstable';position:absolute;top:-1px;left:0;mix-blend-mode:multiply}body div#error p span:before{clip-path:polygon(0 0,100% 0,100% 50%,0 50%);color:#f0f;animation:shiftright 1.5s steps(2) infinite}body div#error p span:after{clip-path:polygon(0 100%,100% 100%,100% 50%,0 50%);color:#0ff;animation:shiftleft 1.7s steps(2) infinite}@-moz-keyframes dance{0%,84%,94%{transform:skew(0)}85%{transform:skew(5deg)}90%{transform:skew(-5deg)}98%{transform:skew(3deg)}}@-webkit-keyframes dance{0%,84%,94%{transform:skew(0)}85%{transform:skew(5deg)}90%{transform:skew(-5deg)}98%{transform:skew(3deg)}}@-o-keyframes dance{0%,84%,94%{transform:skew(0)}85%{transform:skew(5deg)}90%{transform:skew(-5deg)}98%{transform:skew(3deg)}}@keyframes dance{0%,84%,94%{transform:skew(0)}85%{transform:skew(5deg)}90%{transform:skew(-5deg)}98%{transform:skew(3deg)}}@-moz-keyframes shiftleft{0%,100%,87%{transform:translate(0,0) skew(0)}84%,90%{transform:translate(-8px,0) skew(20deg)}}@-webkit-keyframes shiftleft{0%,100%,87%{transform:translate(0,0) skew(0)}84%,90%{transform:translate(-8px,0) skew(20deg)}}@-o-keyframes shiftleft{0%,100%,87%{transform:translate(0,0) skew(0)}84%,90%{transform:translate(-8px,0) skew(20deg)}}@keyframes shiftleft{0%,100%,87%{transform:translate(0,0) skew(0)}84%,90%{transform:translate(-8px,0) skew(20deg)}}@-moz-keyframes shiftright{0%,100%,87%{transform:translate(0,0) skew(0)}84%,90%{transform:translate(8px,0) skew(20deg)}}@-webkit-keyframes shiftright{0%,100%,87%{transform:translate(0,0) skew(0)}84%,90%{transform:translate(8px,0) skew(20deg)}}@-o-keyframes shiftright{0%,100%,87%{transform:translate(0,0) skew(0)}84%,90%{transform:translate(8px,0) skew(20deg)}}@keyframes shiftright{0%,100%,87%{transform:translate(0,0) skew(0)}84%,90%{transform:translate(8px,0) skew(20deg)}}@-moz-keyframes blink{0%,100%,50%,85%{color:#000}87%,95%{color:transparent}}@-webkit-keyframes blink{0%,100%,50%,85%{color:#000}87%,95%{color:transparent}}@-o-keyframes blink{0%,100%,50%,85%{color:#000}87%,95%{color:transparent}}@keyframes blink{0%,100%,50%,85%{color:#000}87%,95%{color:transparent}}
    </style>
    EOT,
       <<<EOT
    <h1 aria-label="$error Error" style="font-size: calc(3em + 9vw);">$error</h1>
    <h2>$error_message</h2>
    <h3>$error_message_advanced</h3>
    <style>
    body {
        justify-content: center;
        align-items: center;
        font-family: 'Open Sans', Arial, sans-serif;
        text-align: center;
        color: #fff;
        background-image: linear-gradient(-225deg, #cf2778, #7c64d5, #4cc3ff);
    }
    </style>
    EOT
    ];
    $credits_list = [
        "<a target='_blank' href='https://codepen.io/yexx'>Yeshua Emanuel Braz</a>",
        "<a target='_blank' href='https://codepen.io/GeorgePark'>George W. Park</a>"
    ];
    $key = isset($_GET["force_page"]) ? $_GET["force_page"] : array_rand($error_templates);
    $credits = $credits_list[$key];
    echo($error_templates[$key]);

    $nonce = false;
    try {
        if (is_object($tools)) $nonce = $tools->script_nonce;
    } catch (\Exception $e) {
    }
?>
<br><br>
<?php
if(!is_null($game_script_url)){
?>
<div class="games_list" style="margin-left: 20px; text-align: left;">
  While you are waiting, you can play some games:
    <ul>
      <li hidden><a data-game="pong" class="playGame">Pong</a></li>
      <li><a data-game="ld46" class="playGame">What the firetruck</a></li>
    </ul>
</div>
<div class="credits" style="position:absolute;opacity: 0.5;bottom: 5px;right: 5px;">
  Error page based on work by <?php echo($credits); ?>.
</div>
<script src="<?php echo($main_script_url); ?>"<?php if($nonce){ echo("nonce='{$nonce}'"); } ?>></script>
<script src="<?php echo($game_script_url); ?>"<?php if($nonce){ echo("nonce='{$nonce}'"); } ?>></script>
<?php
}
}

if (basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"])) {
    show_error_page();
}
?>