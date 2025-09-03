<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AnimalQuizQuestion;

class AnimalQuizQuestionSeeder extends Seeder
{
    public function run(): void
    {
        $questions = [
            [ 'difficulty'=>'easy','topic'=>'Kỷ lục','text'=>'Loài động vật nào lớn nhất trên cạn?','options'=>['Voi châu Phi','Hươu cao cổ','Hà mã','Tê giác trắng'],'correct'=>0,'explanation'=>'Voi châu Phi là động vật trên cạn lớn nhất hiện nay' ],
            [ 'difficulty'=>'easy','topic'=>'Ăn uống','text'=>'Động vật nào thường ăn lá bạch đàn?','options'=>['Gấu koala','Gấu trúc','Sư tử','Chó sói'],'correct'=>0,'explanation'=>'Koala hầu như chỉ ăn lá bạch đàn' ],
            [ 'difficulty'=>'medium','topic'=>'Hành vi','text'=>'Đàn kiến thường để lại gì giúp đồng loại tìm đường?','options'=>['Vệt mùi hóa học','Âm thanh lớn','Ánh sáng nhấp nháy','Vệt bùn'],'correct'=>0,'explanation'=>'Kiến dùng pheromone tạo đường mùi' ],
            [ 'difficulty'=>'easy','topic'=>'Phân loại','text'=>'Đâu là động vật có vú?','options'=>['Cá heo','Chim sẻ','Rùa','Ếch'],'correct'=>0,'explanation'=>'Cá heo thở bằng phổi và nuôi con bằng sữa' ],
            [ 'difficulty'=>'medium','topic'=>'Môi trường sống','text'=>'Lạc đà thích nghi tốt với kiểu môi trường nào?','options'=>['Sa mạc khô nóng','Rừng mưa','Vùng băng vĩnh cửu','Đầm lầy lạnh'],'correct'=>0,'explanation'=>'Lạc đà có nhiều đặc điểm giúp sống ở sa mạc' ],
            [ 'difficulty'=>'hard','topic'=>'Sinh học','text'=>'Bướm trải qua kiểu biến thái gì?','options'=>['Hoàn toàn','Không biến đổi','Từng phần nhỏ','Chỉ thay màu'],'correct'=>0,'explanation'=>'Bướm có các giai đoạn trứng ấu trùng nhộng trưởng thành' ],
            [ 'difficulty'=>'easy','topic'=>'Âm thanh','text'=>'Động vật nào kêu "ò ó o" vào sáng sớm?','options'=>['Gà trống','Chim cú','Chim cánh cụt','Bồ câu'],'correct'=>0,'explanation'=>'Gà trống gáy báo sáng' ],
            [ 'difficulty'=>'medium','topic'=>'Đặc điểm','text'=>'Hươu đực thường có gì nổi bật trên đầu?','options'=>['Gạc','Mào đỏ','Mũi dài','Vây'],'correct'=>0,'explanation'=>'Gạc hươu đực mọc rồi rụng theo mùa' ],
            [ 'difficulty'=>'medium','topic'=>'Ăn uống','text'=>'Loài nào sau đây là động vật ăn tạp?','options'=>['Gấu','Báo','Trình cánh cụt','Chim ưng'],'correct'=>0,'explanation'=>'Gấu ăn cả thực vật và thịt' ],
            [ 'difficulty'=>'hard','topic'=>'Sinh học','text'=>'Động vật thở bằng mang khi còn là nòng nọc rồi thở bằng phổi khi lớn là loài nào?','options'=>['Ếch','Rắn','Nhím','Cú'],'correct'=>0,'explanation'=>'Ếch biến thái từ nòng nọc thở mang sang trưởng thành thở phổi' ],
            [ 'difficulty'=>'easy','topic'=>'Môi trường sống','text'=>'Cá hề thường sống trong gì để được bảo vệ?','options'=>['Hải quỳ','San hô lửa','Tảo xanh','Vỏ sò rỗng'],'correct'=>0,'explanation'=>'Cá hề chung sống cộng sinh với hải quỳ' ],
            [ 'difficulty'=>'medium','topic'=>'Hành vi','text'=>'Chim di cư chủ yếu để làm gì?','options'=>['Tìm thức ăn và điều kiện tốt','Chơi đùa','Tránh bạn đời','Thay màu lông'],'correct'=>0,'explanation'=>'Di cư giúp tránh thời tiết khắc nghiệt và kiếm ăn' ],
            [ 'difficulty'=>'easy','topic'=>'Phân loại','text'=>'Rùa thuộc nhóm nào?','options'=>['Bò sát','Lưỡng cư','Cá xương','Côn trùng'],'correct'=>0,'explanation'=>'Rùa là bò sát có mai bảo vệ' ],
            [ 'difficulty'=>'hard','topic'=>'Sinh học','text'=>'Ong mật giao tiếp vị trí thức ăn bằng gì?','options'=>['Điệu nhảy lắc','Tiếng rú dài','Phát sáng thân','Đổi màu cánh'],'correct'=>0,'explanation'=>'Điệu nhảy lắc mô tả hướng và khoảng cách' ],
            [ 'difficulty'=>'medium','topic'=>'Đặc điểm','text'=>'Da cá mập cảm nhận điện yếu nhờ cấu trúc gì?','options'=>['Ống Lorenzini','Râu xúc giác','Túi khí','Tuyến mực'],'correct'=>0,'explanation'=>'Ống Lorenzini phát hiện tín hiệu điện sinh học' ],
            [ 'difficulty'=>'easy','topic'=>'Ăn uống','text'=>'Thỏ thường ăn loại thức ăn nào?','options'=>['Cỏ và rau','Thịt sống','Sâu bọ lớn','Cá nhỏ'],'correct'=>0,'explanation'=>'Thỏ là loài ăn cỏ' ],
            [ 'difficulty'=>'medium','topic'=>'Môi trường sống','text'=>'Động vật nào thích nghi tốt với băng giá vùng cực?','options'=>['Gấu trắng','Hươu cao cổ','Sư tử','Linh dương đồng cỏ'],'correct'=>0,'explanation'=>'Gấu trắng có lớp mỡ dày và lông trắng cách nhiệt' ],
            [ 'difficulty'=>'hard','topic'=>'Hành vi','text'=>'Lý do chính cá heo săn theo nhóm?','options'=>['Tăng hiệu quả bắt mồi','Giảm tiếng ồn nước','Đổi màu nhanh','Ngăn dòng chảy'],'correct'=>0,'explanation'=>'Phối hợp nhóm giúp dồn và bắt mồi hiệu quả' ],
            [ 'difficulty'=>'medium','topic'=>'Phân loại','text'=>'Chim cánh cụt khác phần lớn chim ở điểm nào?','options'=>['Không bay mà bơi giỏi','Không có lông','Không đẻ trứng','Có vú nuôi con'],'correct'=>0,'explanation'=>'Chim cánh cụt tiến hóa cánh thành mái chèo' ],
            [ 'difficulty'=>'easy','topic'=>'Âm thanh','text'=>'Ếch đực thường kêu để làm gì?','options'=>['Thu hút bạn tình','Đuổi trời mưa','Làm khô da','Tạo nhiệt'],'correct'=>0,'explanation'=>'Tiếng kêu giúp gọi và phân biệt cá thể' ],
        ];

        foreach ($questions as $q) {
            AnimalQuizQuestion::firstOrCreate(
                ['text' => $q['text']],
                $q
            );
        }
    }
}

