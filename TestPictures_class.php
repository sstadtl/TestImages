<?php

/**
 * Class TestPictures
 * quick php to create images with (pattern|srcimg) in new retina resolutions @2x @3x
 */
class TestPictures {
    /**
     * @var int
     * 0 = Form
     * 1 = Image
     */
    var $mode = 0;
    /**
     * @var int
     * retina-mode 2=@2x 3=@3x
     */
    var $r = 1;
    /**
     * @var int IMG-Type
     * which type of image
     * 0 png
     * 1 jpg
     */
    var $type = 0; //


    /**
     * @var resource  GD-Image
     */
    var $image;
    /**
     * @var int width of the Image
     */
    var $x = 120;
    /**
     * @var int height of the image
     *
     */
    var $y = 120;
    /**
     * @var int pixelwidth of pattern
     */
    var $pwidth = 10;
    /**
     * @var int
     * 0 = pattern
     * 1-n index of image files found in directory
     */
    var $p = 0;
    /**
     * main
     */
    function handle() {
        $this->parseInput();
        switch ($this->mode) {
            case 1:
                $this->renderImage();
                $this->sendImage();
                break;
            default:
                $this->showForm();
        }

    }

    /**
     * parsing form-values with basic checks
     */
    function parseInput() {
        if ($_GET['x']) {
            $x = intval($_GET['x']);
            if ($x<10) {
                $x=10;
            }
            $this->x = $x;
        }
        if ($_GET['y']) {
            $y = intval($_GET['y']);
            if ($y<10) {
                $y=10;
            }
            $this->y = $y;
        }
        if ($_GET['m']) {
            $m = intval($_GET['m']);
            if (in_array($m ,array(0,1))) {
                $this->mode = $m;
            }
        }
        if ($_GET['r']) {
            $r = intval($_GET['r']);
            if (in_array($r,array(1,2,3))) {
                $this->r = $r;
            }
        }
        $this->p = $r = intval($_GET['p']);

    }

    /**
     * creates the image
     */
    function renderImage() {
        if ($this->r>1) {
            $this->pwidth = $this->pwidth * $this->r;
            $this->x = $this->x * $this->r;
            $this->y = $this->y * $this->r;
        }
        $this->image = @imagecreatetruecolor($this->x, $this->y);
        $white = imagecolorallocate($this->image, 255, 255, 255);
        $gray = imagecolorallocate($this->image, 225, 225, 225);
        $red = imagecolorallocate($this->image, 255, 0, 0);


        //background
        imagefill($this->image, 0, 0, $gray);

        if ($this->p) {
            $this->useImage();
        } else {
            $this->createPattern();
        }

        // border
        for ($b=0;$b<$this->r;$b++) {
            imagerectangle ( $this->image , 0+$b , 0+$b , ($this->x-1)-$b , ($this->y-1)-$b , $red);
        }



    }

    /**
     * uses image file from directory
     * @return bool
     */
    function useImage() {
        $files = $this->findImages();
        $pidx = $this->p - 1;
        if (!isset($files[$pidx])) {
            $this->createPattern();
            return false;
        }
        $picture = dirname(__FILE__).DIRECTORY_SEPARATOR.$files[$pidx];
        $idata = getimagesize($picture);
        switch ($idata['mime']){

            default:
                $pic = imagecreatefromjpeg($picture);

        }
        $sx = 0;
        $sy = 0;

        $sw = $idata[0];
        $sh = $idata[1];

        $q = ($sh/$this->y);
        $sw2 = $this->x * $q;
        if ($sw2>$sw) {
            $q2 = $sw2 / $sw;
            $sw2 = $sw;
            $sh2 = $sh / $q2;
        } else {
            $sh2 = $sh;
        }

        if ($sw2<$sw) {
            $sx = intval(round(0.5 * ($sw-$sw2)));
        }

        if ($sh2<$sh) {
            $sy = intval(round(0.5 * ($sh-$sh2)));
        }





        imagecopyresampled($this->image,$pic,0,0,$sx,$sy,(-1 + $this->x),(-1+$this->y), $sw2,$sh2);
        // var_dump($files,$picture);

    }

    /**
     * reads image files from current directory
     * @return array
     */
    function findImages() {

        $files = array();
        if ($handle = opendir(dirname(__FILE__))) {
            while (false !== ($entry = readdir($handle))) {
                if (preg_match('/.*\.(jpg|png)$/isU',$entry)) {
                    $files[] = $entry;
                }

            }
            closedir($handle);
        }

        return $files;
    }

    /**
     * fills images with sample pattern
     */
    function createPattern() {

        // pattern

        $rows = intval(ceil($this->y/$this->pwidth));
        $cols = intval(ceil($this->x/$this->pwidth));

        $pcolor1 = imagecolorallocate($this->image, 255, 189, 189);
        $pcolor2 = imagecolorallocate($this->image, 189, 255, 189);
        $pcolor3 = imagecolorallocate($this->image, 189, 189, 255);
        $pattcols = array($pcolor1,$pcolor2,$pcolor3);

        for ($i=0;$i<$rows;$i++) {
            for ($j=0;$j<$cols;$j++) {
                $x1 = $this->pwidth * $j;
                $y1 = $this->pwidth * $i;
                $x2 = ($this->pwidth * (1+$j))-1;
                $y2 = ($this->pwidth * (1+$i))-1;
                $actcol = $pattcols[(($i+$j)%3)];

                imagefilledrectangle($this->image,$x1, $y1, $x2 , $y2, $actcol);
            }
        }
    }

    /**
     * creates the URL to request the image
     * @return string
     */
    function buildImgUrl() {
        $ret = 'index.php?m=1&x='.$this->x.'&y='.$this->y;
        if ($this->p) {
            $ret.='&p='.$this->p;
        }
        return $ret;
    }

    /**
     * send image to browser
     */
    function sendImage() {

        switch ($this->type) {

            case 1:
                header ('Content-Type: image/jpg');
                imagejpeg($this->image,null,80);
            break;
            default:
                header ('Content-Type: image/png');
                imagepng($this->image);
        }
        imagedestroy($this->image);
    }

    /**
     * HTML Form
     */
    function showForm() {
        ?><html>
<head>
    <title>TestPicture</title>
</head>
<body>
    <form action="index.php" method="get">
        <label for="x">X:</label>
        <input type="text" id="x" name="x" value="<?php echo $this->x ?>"><br>
        <label for="y">Y:</label>
        <input type="text" id="y" name="y" value="<?php echo $this->y ?>"><br>
        <label for="p">Content:</label>
        <select name="p">
            <option value="0">Pattern</option>
            <?php
            $files = $this->findImages();
            foreach ($files as $key => $val) {
                echo '<option value="'.(1+$key).'" '.(($this->p==(1+$key))?' selected':'').'>'.$val.'</option>';
            }

            ?>
        </select><br>

        <input type="submit">
    </form>
    <?php
    $this->findImages();
    ?>

    <img src="<?php echo $this->buildImgUrl() ?>" width="<?php echo $this->x ?>" height="<?php echo $this->y ?>" border="0">
    <img src="<?php echo $this->buildImgUrl() ?>&r=2" width="<?php echo $this->x ?>" height="<?php echo $this->y ?>" border="0">
    <img src="<?php echo $this->buildImgUrl() ?>&r=3" width="<?php echo $this->x ?>" height="<?php echo $this->y ?>" border="0">
</body>
</html>

        <?php
    }
}
?>
