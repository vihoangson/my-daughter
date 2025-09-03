<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Achievement;
use App\Models\User;

class AchievementSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure a parent exists
        $parent = User::where('type','parent')->first();
        if(!$parent){
            $parent = User::create([
                'name' => 'Default Parent',
                'email' => 'default_parent@example.com',
                'password' => bcrypt('password'),
                'type' => 'parent'
            ]);
        }

        $data = [
            [1,'Thể chất','Chạy liên tục 1km','Hoàn thành một thử thách thể thao nhỏ như chạy 1km hoặc chống đẩy 10 cái.'],
            [2,'Thể chất','Đi xe đạp 2 bánh','Đi xe đạp thành thạo và tự tin di chuyển quãng đường ngắn.'],
            [3,'Thể chất','Tham gia thể thao đồng đội','Chơi một môn thể thao đồng đội ít nhất 1 buổi/tuần (bóng đá, bóng rổ, cầu lông, bơi lội).'],
            [4,'Thể chất','Chăm sóc sức khỏe cá nhân','Biết rửa tay đúng cách, giữ vệ sinh răng miệng, ngủ đúng giờ.'],
            [5,'Tinh thần','Quản lý cảm xúc','Khi buồn/giận có thể nói ra thay vì nổi nóng.'],
            [6,'Tinh thần','Giúp đỡ gia đình','Làm việc nhà phù hợp lứa tuổi: dọn bàn ăn, xếp quần áo, gấp chăn màn.'],
            [7,'Tinh thần','Hoàn thành sở thích cá nhân','Tự thực hiện một sản phẩm theo sở thích: vẽ tranh, xếp lego, làm mô hình, học nhảy.'],
            [8,'Học tập','Đọc xong một quyển sách','Đọc hết một quyển sách phù hợp lứa tuổi và kể lại bằng lời của mình.'],
            [9,'Học tập','Giải bài toán nâng cao','Hoàn thành một bài toán nâng cao hoặc thử thách logic ngoài chương trình.'],
            [10,'Học tập','Trình bày ý tưởng','Thuyết trình một ý tưởng hoặc chủ đề trước lớp/ gia đình bằng lời nói, tranh vẽ, hoặc powerpoint đơn giản.'],
            [11,'Thể chất','Học bơi cơ bản','Bơi được ít nhất 10m không cần phao.'],
            [12,'Thể chất','Leo núi nhân tạo','Thử sức với trò chơi leo núi trong nhà và hoàn thành chặng leo đầu tiên.'],
            [13,'Thể chất','Nhảy dây liên tục 50 lần','Rèn sức bền và sự phối hợp qua nhảy dây.'],
            [14,'Thể chất','Tham gia một giải đấu nhỏ','Tham gia giải thể thao của trường/lớp, bất kể thắng thua.'],
            [15,'Tinh thần','Viết nhật ký cảm xúc','Viết lại một lần mình thấy buồn, vui, hoặc tự hào.'],
            [16,'Tinh thần','Chia sẻ đồ chơi','Tự nguyện chia sẻ đồ chơi hoặc sách với bạn bè.'],
            [17,'Tinh thần','Hoàn thành một thử thách kiên nhẫn','Ngồi ghép một bộ lego hoặc puzzle đến khi hoàn thành.'],
            [18,'Tinh thần','Thực hành biết ơn','Viết hoặc nói 3 điều biết ơn trong ngày.'],
            [19,'Học tập','Học thuộc một bài thơ','Đọc thuộc một bài thơ và trình bày lại.'],
            [20,'Học tập','Viết một đoạn văn ngắn','Tự viết đoạn văn 5–7 câu về chủ đề yêu thích.'],
            [21,'Học tập','Tìm hiểu thiên văn cơ bản','Biết tên ít nhất 5 hành tinh trong Hệ Mặt Trời.'],
            [22,'Học tập','Hoàn thành một thí nghiệm khoa học nhỏ','Ví dụ: tạo núi lửa mini bằng baking soda và giấm.'],
            [23,'Học tập','Học 10 từ vựng tiếng Anh mới','Ghi nhớ và dùng được trong câu đơn giản.'],
            [24,'Học tập','Tính nhẩm nhanh','Thực hiện phép tính cộng/trừ trong phạm vi 100 một cách nhanh chóng.'],
            [25,'Xã hội','Giới thiệu bản thân','Tự tin nói tên, tuổi, sở thích trước nhóm bạn.'],
            [26,'Xã hội','Kết bạn mới','Chủ động bắt chuyện và làm quen với một bạn mới.'],
            [27,'Xã hội','Làm việc nhóm','Tham gia hoàn thành một sản phẩm nhóm trong lớp.'],
            [28,'Xã hội','Tham gia hoạt động từ thiện nhỏ','Tặng sách, quần áo hoặc đồ chơi cũ cho người cần.'],
            [29,'Sáng tạo','Vẽ một bức tranh hoàn chỉnh','Hoàn thành bức vẽ có nhân vật, cảnh vật hoặc ý tưởng riêng.'],
            [30,'Sáng tạo','Học một bài hát mới','Thuộc và hát trọn vẹn một bài hát thiếu nhi.'],
            [31,'Sáng tạo','Thử làm đồ thủ công','Tự làm một sản phẩm handmade đơn giản như gấp origami, làm thiệp.'],
            [32,'Sáng tạo','Chụp một bộ ảnh nhỏ','Chụp 5–10 tấm ảnh về một chủ đề (gia đình, thiên nhiên, đồ vật).'],
            [33,'Tinh thần','Tập thiền 3 phút','Ngồi yên, hít thở chậm rãi và tập trung vào hơi thở.'],
            [34,'Thể chất','Đi bộ đường dài','Đi bộ cùng gia đình trong một chuyến dã ngoại.'],
            [35,'Học tập','Tự tra cứu kiến thức','Hỏi người lớn hoặc tìm sách để trả lời một câu hỏi khó.'],
            [36,'Học tập','Tạo một poster đơn giản','Thiết kế poster bằng giấy màu hoặc powerpoint về chủ đề yêu thích.'],
            [37,'Xã hội','Nói lời xin lỗi','Biết xin lỗi khi mắc lỗi với bạn bè hoặc gia đình.'],
            [38,'Xã hội','Khen ngợi người khác','Chân thành khen bạn bè/anh chị vì một việc làm tốt.'],
            [39,'Tinh thần','Đặt mục tiêu tuần','Viết ra một mục tiêu nhỏ và thực hiện trong tuần.'],
            [40,'Tinh thần','Ăn thử món mới','Thử ăn một loại rau hoặc món ăn mà trước đó không dám thử.'],
        ];

        foreach($data as [$id,$category,$title,$description]){
            // Avoid duplicates by title & parent
            if(!Achievement::where('parent_id',$parent->id)->where('name',$title)->exists()){
                Achievement::create([
                    'parent_id' => $parent->id,
                    'category' => $category,
                    'name' => $title,
                    'note' => $description,
                ]);
            }
        }
    }
}

