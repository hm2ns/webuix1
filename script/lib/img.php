<?php class Img_compress
{
    private $img_input;
    private $img_output;
    private $img_src;
    private $format;
    private $quality = 80;
    private $x_input;
    private $y_input;
    private $x_output;
    private $y_output;
    private $resize; /* * 处理上传的文件保存至路径 * @param {string} $tmp_file 上传的临时文件位置 * @param {string} $name_file 上传的文件的原名 * @param {string} $path 要保存的位置和名字(不带扩展名) */
    public function img_move($name_file, $path)
    {
        $temp = explode(".", $name_file);
        $temp = end($temp); //取数组最后一个值为扩展名 
        $path = $path . '.' . $temp;
        $data = ['path' => $path, 'ext' => $temp];
        return $data;
    } // 设置图片 
    public function set_img($img)
    { // 查找格式 
        $ext = strtoupper(pathinfo($img, PATHINFO_EXTENSION)); // JPEG image 
        if (is_file($img) && ($ext == "JPG" or $ext == "JPEG")) {
            $this->format = $ext;
            $this->img_input = ImageCreateFromJPEG($img);
            $this->img_src = $img;
        } // PNG image 
        elseif (is_file($img) && $ext == "PNG") {
            $this->format = $ext;
            $this->img_input = ImageCreateFromPNG($img);
            $this->img_src = $img;
        } // GIF image 
        elseif (is_file($img) && $ext == "GIF") {
            $this->format = $ext;
            $this->img_input = ImageCreateFromGIF($img);
            $this->img_src = $img;
        } // 获取尺寸 
        $this->x_input = imagesx($this->img_input);
        $this->y_input = imagesy($this->img_input);
    } // 设置最大图像大小 (像素) 
    public function set_size($size = 100)
    { // 调整大小 
        $this->x_output = $this->x_input * $size;
        $this->y_output = $this->y_input * $size;
        $this->resize = TRUE;
    } // 设置图像质量 (JPEG only) 
    public function set_quality($quality)
    {
        if (is_int($quality)) {
            $this->quality = $quality;
        }
    } // 保存图片 
    public function save_img($path)
    { // 调整大小 
        if ($this->resize) {
            $this->img_output = ImageCreateTrueColor($this->x_output, $this->y_output);
            ImageCopyResampled($this->img_output, $this->img_input, 0, 0, 0, 0, $this->x_output, $this->y_output, $this->x_input, $this->y_input);
        } // Save JPEG 
        if ($this->format == "JPG" or $this->format == "JPEG") {
            if ($this->resize) {
                imageJPEG($this->img_output, $path, $this->quality);
            } else {
                copy($this->img_src, $path);
            }
        } // Save PNG 
        elseif ($this->format == "PNG") {
            if ($this->resize) {
                imagePNG($this->img_output, $path);
            } else {
                copy($this->img_src, $path);
            }
        } // Save GIF 
        elseif ($this->format == "GIF") {
            if ($this->resize) {
                imageGIF($this->img_output, $path);
            } else {
                copy($this->img_src, $path);
            }
        }
    } // 获取宽度 
    public function get_width()
    {
        return $this->x_input;
    } // 获取高度 
    public function get_height()
    {
        return $this->y_input;
    } // 清除图片缓存 
    public function clear_cache()
    {
        @ImageDestroy($this->img_input);
        @ImageDestroy($this->img_output);
    }
}
