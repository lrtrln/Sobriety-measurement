<?php
ini_set('error_reporting', E_ALL);
error_reporting(E_ALL);

include_once 'functions.php';

$result = run();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Éco-Performance pages web</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style type="text/tailwindcss">
    body {
        @apply bg-sky-900 font-["system-ui"];
    }
    .container {
        @apply flex items-center justify-center mt-8;
    }
    .content {
        @apply px-9 pt-10 pb-14 flex flex-col gap-y-8 text-white rounded-xl max-w-[810px] max-h-[468px];
    }
    .loader {
        @apply fixed top-0 left-0 w-full h-full bg-black bg-opacity-50 items-center justify-center;
    }
    .code {
        @apply font-["courier"] p-4 flex flex-col text-white break-words max-h-[800px]
    }
    .h1 {
        @apply text-4xl pb-4 font-bold underline;
    }
    .button {
        @apply h-auto rounded-xl bg-white text-black py-3 px-6 w-full hover:bg-sky-300 duration-300;
    }
    .button span {
        @apply text-black font-semibold;
    }
    .svg {
        @apply inline w-10 h-10 text-gray-200 animate-spin dark:text-gray-600 fill-blue-600;
    }
    .code a {
        word-break: break-all;
    }
    </style>
</head>
<body class="bg-sky-900">
    <div class="container min-w-full">
        <div class="content">
            <div>
                <h1 class="h1">Analyseur de <strong>sobriété</strong> page web <small>(beta)</small></h1>
                <p class="">
                     Analyse des éléments DOM, du poids des ressources et des requêtes pour réduire et maîtriser la consommation énergétique <small>(et donc l'empreinte carbone d'un site web)</small>.
                </p>
            </div>
            <div>
                <form method="POST" action="" class="flex flex-col space-y-8" id="Form">
                    <div class="flex flex-col gap-y-8 h-7 space-x-2 w-auto">
                    <input type="text" id="url" name="url" required class="font-['courier']  bg-transparent border-2 rounded-full py-4 px-6 text-[16px] leading-[22.4px] font-light placeholder:text-white text-white" placeholder="URL de la page" value="<?=isset($_POST['url']) ? $_POST['url'] : ''?>"/>
                    </div>
                    <div class="flex justify-end">
                        <label>
                            <input type="checkbox" id="pagespeedCheckbox" name="pagespeed" value="1" <?=isset($_POST['pagespeed']) ? 'checked' : ''?>> Inclure analyse Pagespeed
                        </label>
                    </div>
                    <button type="submit" class="button">
                        <span class="">Analyser</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div id="loader" class="fixed top-0 left-0 w-full h-full bg-black bg-opacity-50 items-center justify-center flex hidden">
        <div role="status">
            <svg aria-hidden="true" class="svg" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"/>
            </svg>
            <span class="sr-only">Chargement...</span>
        </div>
    </div>

    <div class="container min-w-full bg-gray-800 overflow-auto">
        <?php if (isset($result) && $result !== false): ?>
        <pre class="code"><?=$result?></pre>
        <?php endif?>
    </div>

    <footer class="container min-w-full text-white underline">
        <a href="https://lrtrln.fr/" title="lrtrln">Par lrtrln</a>
    </footer>

    <script src="main.js"></script>
</body>
</html>