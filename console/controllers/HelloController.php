<?php
namespace console\controllers;

use SebastianBergmann\CodeCoverage\Report\PHP;
use Yii;
use yii\console\Controller;
use console\components\site_parsers\Parser;
use console\components\file_parsers\ArchiveTxt;
use console\components\file_parsers\ArchiveUrl;
use console\components\loader\WpLoader;
use console\components\loader\WpLoaderLenkino;
use console\components\parser\sites\LenkinoVideo;
use console\components\loader\PostHelper;
use yii\db\Exception;

class HelloController extends Controller
{
    /**
     * проерка на тор
     * // Целевой ip
    $ip = implode('.', array_reverse(explode('.', $_SERVER['REMOTE_ADDR'])));
    // Порт
    $port = 80;
    // Адрес вашего сайта
    $addr = '158.69.24.72';


    $ipInfo = dns_get_record("$ip.$port.$addr.ip-port.exitlist.torproject.org");

    var_dump($ipInfo);

    if (count($ipInfo)) {
    $ip = current($ipInfo);
    if (isset($ip['ip']) && $ip['ip'] == '127.0.0.2') {
    // Да, это tor
    }
    }
     */

    public function actionIndex(){

        $archiveTxt = new ArchiveTxt();
        //$archiveUrls = $archiveTxt->parse('/home/vlad/work_data/healthlifemag/medical_articles.txt');
        $archiveUrls = $archiveTxt->parseInline('/home/vkarpenko/work_data/project/healthlifemag/test.txt');

        //print_r($archiveUrls);


        /**
         * @var $parsers \console\components\site_parsers\Parser[]
         */
        $parsers = [];
        foreach ($archiveUrls as $archiveUrl){
            if(!isset($parsers[$archiveUrl->parserClass])){
                try{
                    if(class_exists($archiveUrl->parserClass)){
                        $parsers[$archiveUrl->parserClass] = new $archiveUrl->parserClass();
                    }

                }
                catch (\Exception $exception){
                    echo $archiveUrl->parserClass . ' class not exists' . PHP_EOL;
                }

            }

            if(isset($parsers[$archiveUrl->parserClass])){
                echo $archiveUrl->parseUrl . PHP_EOL;
                try {
                    $parsers[$archiveUrl->parserClass]->parseArticle($archiveUrl->parseUrl, $archiveUrl->url);
                }catch (\Exception $exception){
                      //  echo ' Parser Error(404)' . PHP_EOL;
                        //throw new Exception('404((', 40000);

                       if($exception->getCode() != 40000){
                            throw new Exception($exception->getMessage(), $exception->getCode());
                       }
                       else{
                           echo ' Parser Error(404)' . PHP_EOL;
                       }
                   }
               // break;
            }

        }

    }

    public function actionEnerateRelation( $lastId=6100, $path = '/home/vlad/work_data/lenkino/parsed/www.lenkino.video'){
        $dbph = \Yii::$app->dbph;
        //$dbph = \Yii::$app->db;

        $parser = new LenkinoVideo($path);
        $loader = new WpLoaderLenkino();
        while (($posts = $dbph->createCommand('select * from wp_posts where post_type="post" and post_status="publish" and id >'.$lastId.' order by id asc limit 30')->queryAll())){

            $postsIds = \yii\helpers\ArrayHelper::getColumn($posts, 'ID');
            $crpRelations= $dbph->createCommand('select post_id from wp_postmeta where post_id in('.implode(",",$postsIds ).') and meta_key="crp_relations_to"')->queryColumn();

            $crpRelationsNeed = array_diff($postsIds, $crpRelations);
            if(empty($crpRelationsNeed)){
                $lastId = max($postsIds);
                continue;
            }

            $crpRelationsNeedR = $dbph->createCommand('select lenkino_id, wp_id from import_post_mapper where wp_id in('.implode(",",$crpRelationsNeed).')')->queryAll();

            foreach ($crpRelationsNeedR as $postsRow){
                $lastId = $postsRow['wp_id'];
                $postsLenId = $postsRow['lenkino_id'];
                $fileAlreadyLoaded = $path . DIRECTORY_SEPARATOR . 'dst_loaded' .  DIRECTORY_SEPARATOR .'_'.$postsLenId.'.html';
                if(!is_file($fileAlreadyLoaded)){
                    continue;
                }
                $post = $parser->createPostFromFile($fileAlreadyLoaded);
                if(!empty($post['relateds'])){
                        $postsReletedLenkinoIds = \yii\helpers\ArrayHelper::getColumn($post['relateds'], 'data-id');

                        $relatedPosts = $dbph->createCommand('select wp_id from import_post_mapper where lenkino_id in('.implode(",",$postsReletedLenkinoIds).')')->queryColumn();


                        $loader->createRelatedPosts($lastId, $relatedPosts);
                        echo 'Created for post ' . $lastId . ' len: ' . $postsLenId . PHP_EOL;
                      //  die();

                }
            }

        }
    }

    public function actionPost($count = 15, $path = '/home/vlad/work_data/lenkino/parsed/www.lenkino.video'){
        $postedCount = 0;
       /* $shaduleFilePath = $path . '/shadule.txt';

        if(file_exists($shaduleFilePath)){
            $shaduleFile = json_decode(file_get_contents($path . '/shadule.txt' ), true) ;

            if(date('Y-m-d') == $shaduleFile['date']){
                $postedCount = $shaduleFile['count'];
                $count = $count - $shaduleFile['count'];
                if($count <= 0){
                    echo 'Today already posted' . PHP_EOL;
                    return;
                }
            }
        }*/

        $loader = new WpLoaderLenkino();
        $parser = new LenkinoVideo($path);

       // $d = dir($path . DIRECTORY_SEPARATOR . 'dst_translate');
        $d = opendir($path . DIRECTORY_SEPARATOR . 'dst_translate'); // open the cwd..also do an err check.

        while(false != ($file = readdir($d))) {
            if(($file != ".") and ($file != "..") and ($file != "index.php")) {
                $files[] = $file; // put in array.
            }
        }
        natsort($files);

        while ($entry = array_shift($files)) {
            if(!is_file($path . DIRECTORY_SEPARATOR . 'dst_translate' . DIRECTORY_SEPARATOR . $entry)){
               continue;
            }
            $file = $path . DIRECTORY_SEPARATOR . 'dst_translate' . DIRECTORY_SEPARATOR . $entry;
            $fileLoading = $path . DIRECTORY_SEPARATOR . 'loading' .  DIRECTORY_SEPARATOR .$entry;
            rename($file, $fileLoading);

            $fileAlreadyLoaded = $path . DIRECTORY_SEPARATOR . 'dst_loaded' .  DIRECTORY_SEPARATOR .$entry;
            $file = $fileLoading;

echo $file . PHP_EOL;
            $post = $parser->createPostFromFile($file);
            if(!$post){
                throw new Exception('video not found in file');
            }

            $relatedPosts = [];
            /*if(!empty($post['relateds'])){
                foreach ($post['relateds'] as $related){
                    $relatedFile = $path . DIRECTORY_SEPARATOR . 'dst_translate' . DIRECTORY_SEPARATOR . '_' . $related['data-id'] . '.html';

                    if(!($relatedPostsId = $loader->existPost($related['data-id']))){
                        if(is_file($relatedFile)){
                            $postRelated = $parser->createPostFromFile($relatedFile);
                            $postRelated = $this->prepareContent($loader, $postRelated);
                           // print_r($postRelated);
                           // die();
                            $postRelatedId = $loader->loadPost($postRelated);
                            $loader->setPostAsExist($related['data-id'], $postRelatedId);
                            $relatedPosts[] = $postRelatedId;

                            $relatedFileLoaded = $path . DIRECTORY_SEPARATOR . 'dst_loaded' .  DIRECTORY_SEPARATOR . '_' . $related['data-id'] . '.html';;
                            rename($relatedFile, $relatedFileLoaded);
                        }
                    } else {
                        $relatedPosts[] = $relatedPostsId;
                    }
                }
            }*/

            //add image ALT https://github.com/scottgonzalez/node-wordpress/issues/43

            $postId = $loader->existPost($post['id']);
            if(!$postId){
                $post = $this->prepareContent($loader, $post);
                $postId = $loader->loadPost($post);
                if($postId) {
                    $loader->setPostAsExist($post['id'], $postId);
                }
            }

            if(!empty($postId)){
                if(!empty($relatedPosts)) {
                    $loader->createRelatedPosts($postId, $relatedPosts);
                }
                rename($file, $fileAlreadyLoaded);
            }

            //die();

        }
        //$shaduleFile = ['date' => date('Y-m-d'), 'count' => $postedCount];
        //file_put_contents($path . '/shadule.txt', json_encode($shaduleFile));
    }



    public function actionPostPhotoshop($count = 15, $path = '/home/vlad/work_data/photoshop-archicad/parsed/photoshop-archicad.com'){
        $postedCount = 0;

        $loader = new \console\components\loader\WpLoaderPhotoshopArchicad();
        $parser = new \console\components\parser\sites\PhotoshopArchicad($path);

        $d = dir($path . DIRECTORY_SEPARATOR . 'dst'); // open the cwd..also do an err check.

        while (false !== ($entry = $d->read())) {
            if(!is_file($path . DIRECTORY_SEPARATOR . 'dst' . DIRECTORY_SEPARATOR . $entry)){
                continue;
            }
            $file = $path . DIRECTORY_SEPARATOR . 'dst' . DIRECTORY_SEPARATOR . $entry;

            $fileLoading = $path . DIRECTORY_SEPARATOR . 'loading' .  DIRECTORY_SEPARATOR .$entry;
   // rename($file, $fileLoading);
    copy($file, $fileLoading);

            $fileLoading = '/home/vlad/work_data/photoshop-archicad/parsed/photoshop-archicad.com/dst/_urok-45-izmereniya-v-detalyach-v-kompas-3d-dlina-rebra-i-ploschad.html.html';


            $fileAlreadyLoaded = $path . DIRECTORY_SEPARATOR . 'dst_loaded' .  DIRECTORY_SEPARATOR .$entry;
            $file = $fileLoading;

            echo $file . PHP_EOL;
            $post = $parser->createPostFromFile($file);

            print_r($post);
            $taxonomies = null;
            $parentCatId = null;
            if(isset($post['parentCategoryUrl']) && $post['parentCategoryUrl'] != '/index.html'){
                $taxonomies = $loader->blog()->getTerms('category');
                $parentCatId = $loader->findCategory($taxonomies, $post['parentCategoryUrl']);

                if(!$parentCatId){
                    //create parent cat
                    $parentCategoryUrlParts = explode('/', $post['parentCategoryUrl']);

                    $CategoryUrlPartsLast = count($parentCategoryUrlParts) - 1;
                    $CatName = $parentCategoryUrlParts[$CategoryUrlPartsLast];
                    $CatUrl = str_replace('.html', '', $CatName);
//todo parse cat name

                    $newPost_url = parse_url($post['url'], PHP_URL_PATH);
                    $newPost_url = str_replace('../', '', $newPost_url);
                    $parenCat = $loader->blog()->newTerm($CatUrl, 'category', $CatUrl, '', [
                        'custom_fields' => [
                            [
                                'key'   => 'post_url',
                                'value' => $newPost_url
                            ]
                        ]
                    ]);
                    var_dump($parenCat);
                    $parentCatId = $parenCat['term_id'];
                }
            }

            if($post['type'] == 'post'){
                //create post
                $postId = $loader->loadPost([
                    'title' => $post['title'],
                    'content' => $post['content'],
                    'category_id' => $parentCatId
                ]);
            } else {
                //create category
                $taxonomies = !empty($taxonomies) ? $taxonomies : $loader->blog()->getTerms('category');
                $createCategoryUrl = parse_url($post['url'], PHP_URL_PATH);
                $createCategoryUrl = str_replace('../', '', $createCategoryUrl);
                $parentCurrentCatId = $loader->findCategory($taxonomies, $createCategoryUrl);
                if($parentCurrentCatId){
                    $loader->blog()->editTerm($parentCurrentCatId, 'category', array(
                        'description' => $post['content']
                    ));
                } else {
                    $post = $loader->prepareImages($post);

                    $PostUrlParts = explode('/', $post['url']);
                    $PostUrlPartsLast = count($PostUrlParts) - 1;
                    $PostName = $PostUrlParts[$PostUrlPartsLast];
                    $PostUrl = str_replace('.html', '', $PostName);

                    $loader->blog()->newTerm($post['title'], 'category', $PostUrl, $post['content'], $parentCatId, [
                        'custom_fields' => [
                            [
                                'key'   => 'post_url',
                                'value' => $createCategoryUrl
                            ]
                        ]
                    ]);
                }
            }

            //rename($file, $fileAlreadyLoaded);
            die();

        }
    }

    /**
     * @param LenkinoVideo $parser
     * @param WpLoaderLenkino $loader
     * @param $file
     * @return mixed
     * @throws Exception
     */
    public function prepareContent($loader, $post){


        $post['content'] = PostHelper::prepareContent($post['content']);

        $post['categories'] = array_merge(
            $loader->prepareCategories($post['categories']),
            $loader->prepareStars($post['stars']),
            $loader->prepareChanels($post['studios'])
        );
        $post['images'] = $loader->prepareImages($post);
        $post['content'] = $loader->prepareContent($post);
        return $post;

    }

   // public function actionParse($url = 'https://www.lenkino.video/porno'){
    public function actionParse($url = 'http://photoshop-archicad.com/video-uroki-kompas-3d/urok-45-izmereniya-v-detalyach-v-kompas-3d-dlina-rebra-i-ploschad.html'){

      //  print_r( json_decode( file_get_contents('/home/vlad/work_data/lenkino/parsed/www.lenkino.video/dst/_26783.html') ) );
        $path = '/home/vlad/work_data/photoshop-archicad/parsed';
        $host = parse_url($url, PHP_URL_HOST);

        $queue = new \console\components\parser\Queue($path . DIRECTORY_SEPARATOR . $host . '.csv');

        $parser = new \console\components\parser\sites\PhotoshopArchicad($path);
        $queue->addLinks([$url]);

        $queueUrl = $url;
        while ( ($queueUrl = $queue->get()) ){
            try {
                $srcPath = $parser->getSrcPath($queueUrl);

                //if(!file_exists($srcPath)){

                    $links = $parser->parse($queueUrl);

                //}


            }catch (\Exception $exception){
                if($exception->getCode() != 404){
                    throw new Exception($exception->getMessage(), $exception->getCode());
                }
                else{
                    echo ' Parser Error(404): ' . $queueUrl . PHP_EOL;
                }
                continue;
            }

            if(!empty($links)){
                $queue->addLinks($links);
            }
            if(!(rand(1,10)%10)){ //25% sleep
                echo 'sleep' . PHP_EOL;
                sleep(2);
            }

        }
    }


    public function actionParseLovepanky($url = 'https://www.lovepanky.com/men'){

        $path = '/home/vlad/work_data/lovepanky/parsed';
        $host = parse_url($url, PHP_URL_HOST);

        $queue = new \console\components\parser\Queue($path . DIRECTORY_SEPARATOR . $host . '.csv');

        $parser = new \console\components\parser\sites\LovepankyCom($path);
        $queue->addLinks([$url]);

        $queueUrl = $url;
        while ( ($queueUrl = $queue->get()) ){
            try {
                echo $queueUrl . PHP_EOL;


                //if(!file_exists($srcPath)){

                $links = $parser->parse($queueUrl);

                //}


            }catch (\Exception $exception){
                if($exception->getCode() != 404){
                    throw new Exception($exception->getMessage(), $exception->getCode());
                }
                else{
                    echo ' Parser Error(404): ' . $queueUrl . PHP_EOL;
                }
                continue;
            }

            if(!empty($links)){
                $queue->addLinks($links);
            }
            if(!(rand(1,10)%10)){ //25% sleep
                echo 'sleep' . PHP_EOL;
                sleep(2);
            }

        }
    }


    public function actionPostBoat($count = 15, $path = '/home/vlad/work_data/sailo/parsed/blog.sailo.com'){

        $parser = new \console\components\parser\sites\HongkiatCom($path);
        $loader = new WpLoader($path);

        // $d = dir($path . DIRECTORY_SEPARATOR . 'dst_translate');
        $d = opendir($path . DIRECTORY_SEPARATOR . 'dst'); // open the cwd..also do an err check.

        while(false != ($file = readdir($d))) {
            if(($file != ".") and ($file != "..") and ($file != "index.php")) {
                $files[] = $file; // put in array.
            }
        }
        natsort($files);


        $catMapper = [
            'Guides' => 'Guides',//2,
            'Reviews' => 'Reviews', //3,
            'Entertainments' => 'Entertainments', //4,
        ];

        $loadingDir = $path . DIRECTORY_SEPARATOR . 'loading';
        if(!is_dir($loadingDir)) mkdir($loadingDir);
        $dstLoadedDir = $path . DIRECTORY_SEPARATOR . 'dst_loaded';
        if(!is_dir($dstLoadedDir)) mkdir($dstLoadedDir);

        while ($entry = array_shift($files)) {

            $file = $path . DIRECTORY_SEPARATOR . 'dst' . DIRECTORY_SEPARATOR . $entry;
          //  $file = $path . DIRECTORY_SEPARATOR . 'dst' . DIRECTORY_SEPARATOR . '_men_dating-women-tips-for-men_dating-a-girl-whos-on-the-rebound.html';

            $fileLoading = $loadingDir . DIRECTORY_SEPARATOR .$entry;
            rename($file, $fileLoading);

            $fileAlreadyLoaded = $dstLoadedDir . DIRECTORY_SEPARATOR .$entry;
            $file = $fileLoading;

            echo $file . PHP_EOL;
            $post = $parser->createPostFromFile($file);
            if(!$post){
                throw new Exception('video not found in file');
            }

            $post['title'] = PostHelper::prepareContent($post['title']);
            $post['content'] = PostHelper::prepareContent($post['content']);
            $post['content'] = PostHelper::removeAttributes(\phpQuery::newDocumentHTML($post['content']));
            $post['content'] = PostHelper::clearAHref(\phpQuery::newDocumentHTML($post['content']));

            $contentDom = \phpQuery::newDocumentHTML($post['content']);
            $activeObjects = $contentDom->find('noscript');
            foreach ($activeObjects as $elem) {
                $pq = pq($elem);
                $pq->remove();
            }
            $post['content'] = $contentDom->html();


            $post['category_id'] = $catMapper[array_rand($catMapper)];

            $image = $loader->loadImage($post['thumbSrc']);
            if($image){
                $post['thumbnail_id'] = $image['attachment_id'];
            }

            $postId = $loader->loadPost($post);


            //die();
            rename($file, $fileAlreadyLoaded);

        }
        //$shaduleFile = ['date' => date('Y-m-d'), 'count' => $postedCount];
        //file_put_contents($path . '/shadule.txt', json_encode($shaduleFile));
    }


    public function actionPostBoat2($count = 15, $path = '/home/vlad/work_data/bluebnc/parsed/blog.bluebnc.com'){

        $parser = new \console\components\parser\sites\HongkiatCom($path);
        $loader = new WpLoader($path);

        // $d = dir($path . DIRECTORY_SEPARATOR . 'dst_translate');
        $d = opendir($path . DIRECTORY_SEPARATOR . 'dst'); // open the cwd..also do an err check.

        while(false != ($file = readdir($d))) {
            if(($file != ".") and ($file != "..") and ($file != "index.php")) {
                $files[] = $file; // put in array.
            }
        }
        natsort($files);


        $catMapper = [
            'Guides' => 'Guides',//2,
            'Reviews' => 'Reviews', //3,
            'Entertainments' => 'Entertainments', //4,
        ];

        $loadingDir = $path . DIRECTORY_SEPARATOR . 'loading';
        if(!is_dir($loadingDir)) mkdir($loadingDir);
        $dstLoadedDir = $path . DIRECTORY_SEPARATOR . 'dst_loaded';
        if(!is_dir($dstLoadedDir)) mkdir($dstLoadedDir);

        while ($entry = array_shift($files)) {

            $file = $path . DIRECTORY_SEPARATOR . 'dst' . DIRECTORY_SEPARATOR . $entry;
            //  $file = $path . DIRECTORY_SEPARATOR . 'dst' . DIRECTORY_SEPARATOR . '_men_dating-women-tips-for-men_dating-a-girl-whos-on-the-rebound.html';

            $fileLoading = $loadingDir . DIRECTORY_SEPARATOR .$entry;
            rename($file, $fileLoading);

            $fileAlreadyLoaded = $dstLoadedDir . DIRECTORY_SEPARATOR .$entry;
            $file = $fileLoading;
           // $file = '/home/vlad/work_data/bluebnc/parsed/blog.bluebnc.com/loading/_en_post_6-amazing-things-to-do-on-your-yacht-charter-in-ibiza.html';

            echo $file . PHP_EOL;
            $post = $parser->createPostFromFile($file);
            if(!$post){
                throw new Exception('video not found in file');
            }

            $post['title'] = PostHelper::prepareContent($post['title']);
            $post['title'] =  \phpQuery::newDocumentHTML( $post['title'])->text();
           // var_dump( $post['title']);
            //die();

            $post['content'] = PostHelper::prepareContent($post['content']);
            $post['content'] = PostHelper::removeAttributes(\phpQuery::newDocumentHTML($post['content']));
            $post['content'] = PostHelper::clearAHref(\phpQuery::newDocumentHTML($post['content']));

            $contentDom = \phpQuery::newDocumentHTML($post['content']);
            $activeObjects = $contentDom->find('noscript');
            foreach ($activeObjects as $elem) {
                $pq = pq($elem);
                $pq->remove();
            }
            $post['content'] = $contentDom->html();


            $post['category_id'] = $catMapper[array_rand($catMapper)];

            echo PHP_EOL . 'thhh: ' . $post['thumbSrc']. PHP_EOL;
            $post['thumbSrc'] = $loader->resolveUrl($post['url'],$post['thumbSrc']);
            $image = $loader->loadImage($post['thumbSrc']);
            if($image){
                $post['thumbnail_id'] = $image['attachment_id'];
            }

            $postId = $loader->loadPost($post);


           // die();
            rename($file, $fileAlreadyLoaded);

        }
        //$shaduleFile = ['date' => date('Y-m-d'), 'count' => $postedCount];
        //file_put_contents($path . '/shadule.txt', json_encode($shaduleFile));
    }




    public function actionParseBlogSailoCom($url = 'http://blog.sailo.com/articles/'){
   // public function actionParseHongkiat($url = 'https://www.hongkiat.com/blog/achieve-maximum-productivity/'){

        $path = '/home/vlad/work_data/sailo/parsed';
        $host = parse_url($url, PHP_URL_HOST);

        $queue = new \console\components\parser\Queue($path . DIRECTORY_SEPARATOR . $host . '.csv');

        $parser = new \console\components\parser\sites\HongkiatCom($path);
        $queue->addLinks([$url]);


       /*  $queueUrl = 'https://www.hongkiat.com/blog/15-noteworthy-websites-that-changed-the-internet/';
        echo $queueUrl . PHP_EOL;
        $links = $parser->parse($queueUrl);
        die();
*/
        $queueUrl = $url;
        while ( ($queueUrl = $queue->get()) ){
            try {
                echo $queueUrl . PHP_EOL;
                $links = $parser->parse($queueUrl);
         //       die();


            }catch (\Exception $exception){
                if($exception->getCode() != 404){
                    throw new Exception($exception->getMessage(), $exception->getCode());
                }
                else{
                    echo ' Parser Error(404): ' . $queueUrl . PHP_EOL;
                }
                continue;
            }

            if(!empty($links)){
                $queue->addLinks($links);
            }
            if(!(rand(1,10)%10)){ //25% sleep
                echo 'sleep' . PHP_EOL;
                sleep(2);
            }

        }
    }

    public function actionParseBlogBluebncCom($url = 'https://blog.bluebnc.com/en/'){
        // public function actionParseHongkiat($url = 'https://www.hongkiat.com/blog/achieve-maximum-productivity/'){

        $path = '/home/vlad/work_data/bluebnc/parsed';
        $host = parse_url($url, PHP_URL_HOST);

        $queue = new \console\components\parser\Queue($path . DIRECTORY_SEPARATOR . $host . '.csv');

        $parser = new \console\components\parser\sites\BluebncCom($path);
        $queue->addLinks([$url]);


        /*  $queueUrl = 'https://www.hongkiat.com/blog/15-noteworthy-websites-that-changed-the-internet/';
         echo $queueUrl . PHP_EOL;
         $links = $parser->parse($queueUrl);
         die();
 */
        $queueUrl = $url;
        while ( ($queueUrl = $queue->get()) ){
            try {
                echo $queueUrl . PHP_EOL;
                $links = $parser->parse($queueUrl);
                //       die();


            }catch (\Exception $exception){
                if($exception->getCode() != 404){
                    throw new Exception($exception->getMessage(), $exception->getCode());
                }
                else{
                    echo ' Parser Error(404): ' . $queueUrl . PHP_EOL;
                }
                continue;
            }

            if(!empty($links)){
                $queue->addLinks($links);
            }
            if(!(rand(1,10)%10)){ //25% sleep
                echo 'sleep' . PHP_EOL;
                sleep(2);
            }

        }
    }

    public function actionFossbytes($url = 'https://fossbytes.com/category/how-to/'){
    //public function actionFossbytes($url = 'https://fossbytes.com/kodi-not-working-issues-troubleshooting-tips/'){

        $path = '/home/vlad/work_data/fossbytes/parsed';
        $host = parse_url($url, PHP_URL_HOST);

        $queue = new \console\components\parser\Queue($path . DIRECTORY_SEPARATOR . $host . '.csv');

        $parser = new \console\components\parser\sites\FossbytesCom($path);
        $queue->addLinks([$url]);

        $queueUrl = $url;
        while ( ($queueUrl = $queue->get()) ){
            try {
                echo $queueUrl . PHP_EOL;
                $links = $parser->parse($queueUrl);
                //       die();


            }catch (\Exception $exception){
                if($exception->getCode() != 404){
                    throw new Exception($exception->getMessage(), $exception->getCode());
                }
                else{
                    echo ' Parser Error(404): ' . $queueUrl . PHP_EOL;
                }
                continue;
            }

            if(!empty($links)){
                $queue->addLinks($links);
            }
            if(!(rand(1,10)%10)){ //25% sleep
                echo 'sleep' . PHP_EOL;
                sleep(2);
            }

        }
    }




    public function actionTranslateJson($path = '/home/vlad/work_data/lenkino/parsed/www.lenkino.video'){
        $wrongCnt = 0;
        $Translator = new \console\components\translator\Freeonlinetranslators();
       // $Translator->setProxy(['proxy_ip' => '127.0.0.1', 'proxy_port' =>8101]);

        $d = dir($path . DIRECTORY_SEPARATOR . 'dst');
        while (false !== ($entry = $d->read())) {
            $filePath = $d->path . DIRECTORY_SEPARATOR  . $entry;
            $filePathTranslated = $path . DIRECTORY_SEPARATOR . 'dst_translate' . DIRECTORY_SEPARATOR . $entry;
            $filePathAlreadyTranslated = $path . DIRECTORY_SEPARATOR . 'dst_already_translate' . DIRECTORY_SEPARATOR . $entry;
            echo $filePath . PHP_EOL;
           // echo '---------------' . PHP_EOL ;
            if(is_file($filePath)){
                //try{
                    $data = json_decode(file_get_contents($filePath), true);

                    $title = null; $content = null;
                   if(!empty($data['title'])){
                        $title = $Translator->translate(strip_tags($data['title']));
                        if(!empty($data['title']) && empty($title)){
                            echo 'Wrong Translate' . PHP_EOL;
                            $wrongCnt++;
                            if($wrongCnt > 3){
                                break;
                            }
                            continue;
                        }
                        if(!empty($title)){
                            $data['title_eng'] = $title;
                        }

                    }
                    if(!empty($data['content'])){
                        $content = $Translator->translate(strip_tags($data['content']));
                        if(!empty($content)){
                            $data['content_eng'] = $content;
                        }
                    }

                    if(!empty($data['stars'])){
                        foreach ($data['stars'] as $starKey => $starRow){
                            $nameEng = $starRow['name'];
                            if(preg_match('|[а-яА-Я]+|u', $starRow['name'])){
                                $nameEng = $Translator->translate(strip_tags($starRow['name']));
                            }
                            $data['stars'][$starKey]['name_eng'] = $nameEng;
                        }
                    }


                    if(!empty($title)){
                        file_put_contents($filePathTranslated, json_encode($data));
                        rename($filePath, $filePathAlreadyTranslated);
                    }
                    if(!(rand(1,10)%10)){ //25% sleep
                        echo 'sleep' . PHP_EOL;
                        sleep(2);
                    }

                /*}catch (\Exception $exception){
                }*/
            }
        }
        $d->close();
    }

        public function actionTranslate($path = '/home/vlad/work_data/healthlifemag/parsed/doc.ua'){
        /*$d = dir($path . DIRECTORY_SEPARATOR . 'dst_translate');
        while (false !== ($entry = $d->read())) {
            if(is_file($path . DIRECTORY_SEPARATOR . 'dst_already_translate/'.$entry)){
                rename($path . DIRECTORY_SEPARATOR . 'dst_already_translate/'.$entry,
                    $path . DIRECTORY_SEPARATOR . 'tt/'.$entry);
            }
        }
        die();*/


        $Translator = new \console\components\translator\Translator();
        $HtmlProcessor = new \console\components\translator\HtmlProcessor();

        $filePath = $path . '/dst/_bolezn_kandidoz_simptomy-i-lechenie-molochnicy-izbavlyaemsya-ot-kandidoza.html';
        $filePathTranslated = $path . DIRECTORY_SEPARATOR . 'dst_translate' . DIRECTORY_SEPARATOR . '_bolezn_kandidoz_simptomy-i-lechenie-molochnicy-izbavlyaemsya-ot-kandidoza.html';


      /*  $html = $Translator->translateHtml( file_get_contents($filePath) );
        $html = $HtmlProcessor->process($html);
        file_put_contents($filePathTranslated, $html);
        die();
*/
        $wrongCnt = 0;
        $d = dir($path . DIRECTORY_SEPARATOR . 'dst');

        while (false !== ($entry = $d->read())) {
            $filePath = $d->path . DIRECTORY_SEPARATOR  . $entry;
            $filePathTranslated = $path . DIRECTORY_SEPARATOR . 'dst_translate' . DIRECTORY_SEPARATOR . $entry;
            $filePathAlreadyTranslated = $path . DIRECTORY_SEPARATOR . 'dst_already_translate' . DIRECTORY_SEPARATOR . $entry;
            echo $filePath . PHP_EOL;
            echo $filePathTranslated . PHP_EOL ;
            echo '---------------' . PHP_EOL ;
            if(is_file($filePath) && !is_file($filePathTranslated)){

                $html = $Translator->translateHtml( file_get_contents($filePath) );
                if(empty($html)){
                    echo 'Wrong Translate' . PHP_EOL;
                    $wrongCnt++;
                    if($wrongCnt > 3){
                        break;
                    }
                    continue;
                }
                $html = $HtmlProcessor->process($html);
                file_put_contents($filePathTranslated, $html);
                rename($filePath, $filePathAlreadyTranslated);

                if(!(rand(1,10)%10)){ //25% sleep
                    echo 'sleep' . PHP_EOL;
                    sleep(2);
                }

            }
        }
        $d->close();
    }

    public function actionDeterminateCat( $last_id = 0){
          /*
                    * @var \yii\db\Connection $dbph
                    */
            $dbph = \Yii::$app->dbph;
           // $dbph = \Yii::$app->db;
///home/vlad/projects/len_ftp/wp-content/related_cat/242.json

        $d = dir('/home/vlad/projects/len_ftp/wp-content/related_cat');
        $m = "";
        while (false !== ($entry = $d->read())) {
            if($entry != '.' && $entry != '..'){
                $data = json_decode( file_get_contents('/home/vlad/projects/len_ftp/wp-content/related_cat/'.$entry));
                foreach ($data as $item){
                    $m .= "<url><loc>https://freeoporn.com/" . (int)$entry . "+".$item."</loc><lastmod>2019-01-24T17:32:58+00:00</lastmod></url>" . PHP_EOL;
                }

            }
            file_put_contents('/home/vlad/tmp/fast_links_sitemap.xml', $m);
        }


die();
        $loader = new WpLoaderLenkino();
        $taxonomy = $loader->blog()->getTerms('category');
        $createdCategories = [];
        $map = [];
        foreach ($taxonomy as $taxonomyItem){
            if($taxonomyItem['parent'] == WpLoaderLenkino::CAT_ID){
                $createdCategories[$taxonomyItem['slug']] = $taxonomyItem;
            }
        }


        $url = 'https://www.lenkino.video/porno';
        $body = \console\components\parser\CurlParser::request($url);
        $dom = \phpQuery::newDocumentHTML($body);
        foreach( $dom->find('.item-cat a') as $link) {
            $href = $link->getAttribute('href');
            $currentCatUrl = trim(parse_url($href, PHP_URL_PATH), '/');

            $bodyCat = \console\components\parser\CurlParser::request($href);
            $domCat = \phpQuery::newDocumentHTML($bodyCat);
            $catIds = [];
            foreach( $domCat->find('.list-multi a') as $linkCatIntersect) {
                $hrefCat = $linkCatIntersect->getAttribute('href');
               // echo  $hrefCat . PHP_EOL;
                $catUrlCat = trim(parse_url($hrefCat, PHP_URL_PATH), '/');
                $catIds = array_merge($catIds, explode('+', $catUrlCat));
            }

            $catIdsCnt = [];
            foreach ($catIds as $catIdsItem){
                if(!isset($catIdsCnt[$catIdsItem])){
                    $catIdsCnt[$catIdsItem] = 0;
                }
                $catIdsCnt[$catIdsItem]++;
            }

            arsort($catIdsCnt);
            reset($catIdsCnt);
            $currentCatId = key($catIdsCnt);
            echo $currentCatId . ' ' . $currentCatUrl . PHP_EOL;
            unset($catIdsCnt[$currentCatId]);



            /*
                    * @var \yii\db\Connection $dbph
                    */
            $dbph = \Yii::$app->dbph;

            if(!empty($currentCatId)){
                $currentCatIdWp = $dbph->createCommand('select wp_id from import_cat_mapper where lenkino_id=' . $currentCatId)->queryScalar();
                $map[$currentCatIdWp]= [];

                foreach ($catIdsCnt as $catIdR => $catIdRCnt){
                    if(!empty($catIdR)){
                        $wp_id = $dbph->createCommand('select wp_id from import_cat_mapper where lenkino_id=' . $catIdR)->queryScalar();
                        if(!empty($wp_id)) {
                            $map[$currentCatIdWp][] = $wp_id;
                        }
                    }

                }
                print_r($map);
            }


        }
        print_r($map);
        file_put_contents('/home/vlad/work_data/lenkino/related_cat/wp_related_cat_map.json', json_encode($map));
        ;
    }


}