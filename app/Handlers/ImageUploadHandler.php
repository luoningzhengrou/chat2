<?php


namespace App\Handlers;

use Illuminate\Support\Str;

class ImageUploadHandler
{

    //只允许以下后缀名的图片文件上传
    protected $allowed_ext = ['png', 'jpg', 'gif', 'jpeg', 'png'];

    public function save($file, $folder, $file_prefix)
    {
        //构建存储的文件夹规则， 如： uploads/images/avatars/202003/20/
        //文件夹切割能让查找效率更高
        $folder_name = "uploads/images/$folder/" . date("Ym/d");

        //文件具体存储的物理路径， `public_path()` 获取的是 `public` 文件夹的物理路径
        //值如： /home/vagrant/Code/larabbs/public/uploads/images/avatars/202003/20/
        $upload_path = public_path() . '/' . $folder_name;

        //获取文件的后缀名，因图片从剪切板里粘贴是后缀名为空，所以此处确保后缀一直存在
        $extension = strtolower($file->getClientOriginalExtension()) ? : 'png';

        //拼接文件名，加前缀是为了增加辨识度，前缀可以是相关数据的模型 ID 值如 1_1588888888_b2.png
        $filename = $file_prefix . '_' . time() . '_' . Str::random(10) . '.' . $extension;

        //如果上传的不是图片将终止操作
        if (! in_array($extension, $this->allowed_ext)){
            return false;
        }

        //将图片移动到我们的目标存储路径中
        $file->move($upload_path, $filename);

        return [
            'path' => config('app.url') . "/$folder_name/$filename"
        ];
    }
}
