<?php

/**
 * Script para la generación de CAPTCHAS
 *
 * @author  Jose Rodriguez <jose.rodriguez@exec.cl>
 * @license GPLv3
 * @link    http://code.google.com/p/cool-php-captcha
 * @package captcha
 * @version 0.3
 *
 */

namespace Toknot\Share;

use Toknot\Boot\Object;

class SimpleCaptcha extends Object {

    /**
     * Width of the image
     * 
     * @access public
     * @var integer 
     */
    public $width = 200;

    /**
     *  Height of the image
     * 
     * @access public
     * @var integer 
     */
    public $height = 70;

    /**
     * Min word length
     * 
     * @access public
     * @var integer
     */
    public $minWordLength = 5;

    /**
     * Max word length 
     * 
     * Used for dictionary words indicating the word-length
     * for font-size modification purposes
     * 
     * @access public
     * @var integer
     */
    public $maxWordLength = 8;

    /**
     * Background color in RGB-array
     * 
     * @access public
     * @var array
     */
    public $backgroundColor = array(255, 255, 255);

    /**
     * Foreground colors in RGB-array 
     * 
     * @access public
     * @var array This is Two-dimensional array
     */
    public $colors = array(
        array(27, 78, 181), // blue
        array(22, 163, 35), // green
        array(214, 36, 7), // red
    );

    /**
     * Shadow color in RGB-array or null
     * 
     * @access public
     * @var array
     */
    public $shadowColor = null; //array(0, 0, 0);

    /**
     * Horizontal line through the text
     * 
     * @access public
     * @var integer 
     */
    public $lineWidth = 0;

    /**
     * Font configuration
     *
     * - font: TTF file
     * - spacing: relative pixel space between character
     * - minSize: min font size
     * - maxSize: max font size
     * 
     * @access public
     * @var array
     */
    public $fonts = array(
        'Antykwa' => array('spacing' => -3, 'minSize' => 27, 'maxSize' => 30, 'font' => 'AntykwaBold.ttf'),
        'Candice' => array('spacing' => -1.5, 'minSize' => 28, 'maxSize' => 31, 'font' => 'Candice.ttf'),
        'DingDong' => array('spacing' => -2, 'minSize' => 24, 'maxSize' => 30, 'font' => 'Ding-DongDaddyO.ttf'),
        'Duality' => array('spacing' => -2, 'minSize' => 30, 'maxSize' => 38, 'font' => 'Duality.ttf'),
        'Heineken' => array('spacing' => -2, 'minSize' => 24, 'maxSize' => 34, 'font' => 'Heineken.ttf'),
        'Jura' => array('spacing' => -2, 'minSize' => 28, 'maxSize' => 32, 'font' => 'Jura.ttf'),
        'StayPuft' => array('spacing' => -1.5, 'minSize' => 28, 'maxSize' => 32, 'font' => 'StayPuft.ttf'),
        'Times' => array('spacing' => -2, 'minSize' => 28, 'maxSize' => 34, 'font' => 'TimesNewRomanBold.ttf'),
        'VeraSans' => array('spacing' => -1, 'minSize' => 20, 'maxSize' => 28, 'font' => 'VeraSansBold.ttf'),
    );

    /**
     * Wave configuracion in X and Y axes
     * 
     * @access public
     * @var integer 
     */
    public $Yperiod = 12;
    public $Yamplitude = 14;
    public $Xperiod = 11;
    public $Xamplitude = 5;

    /**
     * letter rotation clockwise
     * 
     * @access public
     * @var integer
     */
    public $maxRotation = 8;

    /**
     * Internal image size factor (for better image quality)
     * 1: low, 2: medium, 3: high
     * 
     * @access public
     * @var integer
     */
    public $scale = 2;

    /**
     * Blur effect for better image quality (but slower image processing).
     * Better image results with scale=3
     * 
     * @access public
     * @var boolean
     */
    public $blur = false;

    /**
     * Debug?
     * 
     * @access public
     * @var boolean
     */
    public $debug = false;

    /**
     * Image format: jpeg or png
     * 
     * @access public
     * @var string
     */
    public $imageFormat = 'jpeg';

    /**
     * GD image resources handle
     * 
     * @access public
     * @var resource
     */
    public $im;
    
    /**
     * Path for fonts, the value only is framework of Tool/Fonts
     * 
     * @access protected
     * @var string 
     */
    protected $fontPath = './Fonts/';

    protected function __construct() {
        $this->fontPath = __DIR__ . '/Fonts/';
    }

    public static function singleton() {
        return parent::__singleton();
    }

    /**
     * Create Captcha Image and output, and return Captcha Text, the captcha text which 
     * default is random text
     * 
     * Default usage like below:
     * <code>
     * $captcha = SimpleCaptcha::singleton();
     * 
     * $text = $captcha->CreateImage(); //create image and output the image to browser
     * $_SESSION['code'] = $text;  //set session save the code
     * </code>
     * 
     * Generate your self captcha text like below:
     * <code>
     * $captcha = SimpleCaptcha::singleton();
     * 
     * $text = $captcha->CreateImage('Your code text'); //passed your text
     * </code>
     * 
     * @param string $text Passed one Captcha Text and create image
     * @return string  Return the Captcha Text
     */
    public function CreateImage($text = null) {
        $ini = microtime(true);

        /** Initialization */
        $this->ImageAllocate();

        /** Text insertion */
        if ($text === null) {
            $text = $this->GetCaptchaText();
        }
        $fontcfg = $this->fonts[array_rand($this->fonts)];
        $this->WriteText($text, $fontcfg);

        /** Transformations */
        if (!empty($this->lineWidth)) {
            $this->WriteLine();
        }
        $this->WaveImage();
        if ($this->blur && function_exists('imagefilter')) {
            imagefilter($this->im, IMG_FILTER_GAUSSIAN_BLUR);
        }
        $this->ReduceImage();


        if ($this->debug) {
            imagestring($this->im, 1, 1, $this->height - 8, "$text {$fontcfg['font']} " . round((microtime(true) - $ini) * 1000) . "ms", $this->GdFgColor
            );
        }

        $this->WriteImage();
        $this->Cleanup();
        return $text;
    }

    /**
     * Creates the image resources
     */
    protected function ImageAllocate() {
        // Cleanup
        if (!empty($this->im)) {
            imagedestroy($this->im);
        }

        $this->im = imagecreatetruecolor($this->width * $this->scale, $this->height * $this->scale);

        // Background color
        $this->GdBgColor = imagecolorallocate($this->im, $this->backgroundColor[0], $this->backgroundColor[1], $this->backgroundColor[2]
        );
        imagefilledrectangle($this->im, 0, 0, $this->width * $this->scale, $this->height * $this->scale, $this->GdBgColor);

        // Foreground color
        $color = $this->colors[mt_rand(0, sizeof($this->colors) - 1)];
        $this->GdFgColor = imagecolorallocate($this->im, $color[0], $color[1], $color[2]);

        // Shadow color
        if (!empty($this->shadowColor) && is_array($this->shadowColor) && sizeof($this->shadowColor) >= 3) {
            $this->GdShadowColor = imagecolorallocate($this->im, $this->shadowColor[0], $this->shadowColor[1], $this->shadowColor[2]
            );
        }
    }

    /**
     * Text generation
     *
     * @return string Text
     */
    protected function GetCaptchaText() {
        return $this->GetRandomCaptchaText();
    }

    /**
     * Random text generation
     *
     * @return string Text
     */
    protected function GetRandomCaptchaText($length = null) {
        if (empty($length)) {
            $length = rand($this->minWordLength, $this->maxWordLength);
        }

        $words = "abcdefghijlmnopqrstvwyz";
        $vocals = "aeiou";

        $text = "";
        $vocal = rand(0, 1);
        for ($i = 0; $i < $length; $i++) {
            if ($vocal) {
                $text .= substr($vocals, mt_rand(0, 4), 1);
            } else {
                $text .= substr($words, mt_rand(0, 22), 1);
            }
            $vocal = !$vocal;
        }
        return $text;
    }

    /**
     * Horizontal line insertion
     */
    protected function WriteLine() {

        $x1 = $this->width * $this->scale * .15;
        $x2 = $this->textFinalX;
        $y1 = rand($this->height * $this->scale * .40, $this->height * $this->scale * .65);
        $y2 = rand($this->height * $this->scale * .40, $this->height * $this->scale * .65);
        $width = $this->lineWidth / 2 * $this->scale;

        for ($i = $width * -1; $i <= $width; $i++) {
            imageline($this->im, $x1, $y1 + $i, $x2, $y2 + $i, $this->GdFgColor);
        }
    }

    /**
     * Text insertion
     */
    protected function WriteText($text, $fontcfg = array()) {
        if (empty($fontcfg)) {
            // Select the font configuration
            $fontcfg = $this->fonts[array_rand($this->fonts)];
        }

        // Full path of font file
        $fontfile = $this->fontPath . $fontcfg['font'];


        /** Increase font-size for shortest words: 9% for each glyp missing */
        $lettersMissing = $this->maxWordLength - strlen($text);
        $fontSizefactor = 1 + ($lettersMissing * 0.09);

        // Text generation (char by char)
        $x = 20 * $this->scale;
        $y = round(($this->height * 27 / 40) * $this->scale);
        $length = strlen($text);
        for ($i = 0; $i < $length; $i++) {
            $degree = rand($this->maxRotation * -1, $this->maxRotation);
            $fontsize = rand($fontcfg['minSize'], $fontcfg['maxSize']) * $this->scale * $fontSizefactor;
            $letter = substr($text, $i, 1);

            if ($this->shadowColor) {
                $coords = imagettftext($this->im, $fontsize, $degree, $x + $this->scale, $y + $this->scale, $this->GdShadowColor, $fontfile, $letter);
            }
            $coords = imagettftext($this->im, $fontsize, $degree, $x, $y, $this->GdFgColor, $fontfile, $letter);
            $x += ($coords[2] - $x) + ($fontcfg['spacing'] * $this->scale);
        }

        $this->textFinalX = $x;
    }

    /**
     * Wave filter
     */
    protected function WaveImage() {
        // X-axis wave generation
        $xp = $this->scale * $this->Xperiod * rand(1, 3);
        $k = rand(0, 100);
        for ($i = 0; $i < ($this->width * $this->scale); $i++) {
            imagecopy($this->im, $this->im, $i - 1, sin($k + $i / $xp) * ($this->scale * $this->Xamplitude), $i, 0, 1, $this->height * $this->scale);
        }

        // Y-axis wave generation
        $k = rand(0, 100);
        $yp = $this->scale * $this->Yperiod * rand(1, 2);
        for ($i = 0; $i < ($this->height * $this->scale); $i++) {
            imagecopy($this->im, $this->im, sin($k + $i / $yp) * ($this->scale * $this->Yamplitude), $i - 1, 0, $i, $this->width * $this->scale, 1);
        }
    }

    /**
     * Reduce the image to the final size
     */
    protected function ReduceImage() {
        // Reduzco el tamaño de la imagen
        $imResampled = imagecreatetruecolor($this->width, $this->height);
        imagecopyresampled($imResampled, $this->im, 0, 0, 0, 0, $this->width, $this->height, $this->width * $this->scale, $this->height * $this->scale
        );
        imagedestroy($this->im);
        $this->im = $imResampled;
    }

    /**
     * File generation
     */
    protected function WriteImage() {
        if ($this->imageFormat == 'png' && function_exists('imagepng')) {
            header("Content-type: image/png");
            imagepng($this->im);
        } else {
            header("Content-type: image/jpeg");
            imagejpeg($this->im, null, 80);
        }
    }

    protected function Cleanup() {
        imagedestroy($this->im);
    }

}