<?php
/**
 * BÀI VIẾT CẨM NANG
 *  ► Đã sửa mục này trong TRANG QUẢN TRỊ (/quan-tri/) thì website dùng dữ liệu trong data/noi-dung/ – file này chỉ còn là bản mẫu.
 * Mỗi bài là một phần tử trong mảng. Thêm bài mới bằng cách sao chép một khối [ ... ].
 * - slug: đường dẫn bài viết (chữ thường, không dấu, nối bằng dấu gạch ngang)
 * - date: ngày đăng (YYYY-MM-DD)
 * - image: đường dẫn ảnh, tính từ thư mục assets/img/
 */
return [
    [
        'slug'    => 'chon-mau-ngoc-phi-thuy-hop-menh',
        'title'   => 'Chọn màu ngọc phỉ thúy hợp mệnh theo ngũ hành',
        'date'    => '2026-10-08',
        'author'  => 'Viên Ngọc Phương',
        'image'   => 'products/vong-ngoc-phi-thuy-tim-lavender-2.svg',
        'excerpt' => 'Theo quan niệm phong thủy, mỗi mệnh hợp với những gam màu riêng. Cùng tìm hiểu cách chọn màu ngọc phỉ thúy hợp bản mệnh và hợp cả phong cách của bạn.',
        'content' => <<<'HTML'
<p>Ngoài vẻ đẹp và chất ngọc, nhiều người khi chọn trang sức ngọc phỉ thúy còn quan tâm món ngọc ấy có hợp mệnh hay không. Theo quan niệm phong thủy, mỗi người mang một hành trong ngũ hành và có những gam màu được xem là tương hợp hoặc nên tránh. Bài viết dưới đây giúp bạn nắm được nguyên tắc cơ bản để tự chọn màu ngọc phù hợp cho mình hoặc cho người thân.</p>

<h2>Hiểu nhanh về ngũ hành tương sinh, tương khắc</h2>
<p>Theo ngũ hành, vạn vật được quy về năm yếu tố: Kim, Mộc, Thủy, Hỏa, Thổ. Các hành này tác động qua lại theo hai quy luật chính:</p>
<ul>
<li><strong>Tương sinh</strong> – hỗ trợ, nuôi dưỡng nhau: Thổ sinh Kim, Kim sinh Thủy, Thủy sinh Mộc, Mộc sinh Hỏa, Hỏa sinh Thổ.</li>
<li><strong>Tương khắc</strong> – kìm hãm nhau: Kim khắc Mộc, Mộc khắc Thổ, Thổ khắc Thủy, Thủy khắc Hỏa, Hỏa khắc Kim.</li>
</ul>
<p>Mỗi hành gắn với một nhóm màu riêng. Theo quan niệm phong thủy, màu hợp với bạn gồm <strong>màu bản mệnh</strong> (màu thuộc chính hành của bạn) và <strong>màu tương sinh</strong> (màu của hành sinh ra hành của bạn). Ngược lại, màu thuộc hành khắc với hành của bạn thường được khuyên nên hạn chế.</p>

<h2>Xác định mệnh theo năm sinh âm lịch</h2>
<p>Mệnh ngũ hành được tính theo <strong>năm sinh âm lịch</strong>, không phải năm dương lịch. Tết Nguyên đán rơi vào khoảng cuối tháng 1 đến tháng 2 dương lịch tùy từng năm, nên nếu bạn sinh vào tháng 1 hoặc đầu tháng 2 mà <em>trước ngày Tết</em> của năm đó, bạn vẫn thuộc năm âm lịch trước, và mệnh cũng được tính theo năm âm lịch trước đó.</p>
<p>Bạn có thể tra bảng năm sinh – mệnh theo năm âm lịch, hoặc nhắn ngày tháng năm sinh cho Viên Ngọc Phương, chúng tôi sẽ hỗ trợ bạn tra cứu.</p>

<h2>Bảng chọn màu ngọc phỉ thúy theo mệnh</h2>
<p>Bảng dưới đây tổng hợp màu tương hợp, màu nên tránh và gợi ý màu ngọc phỉ thúy cho từng mệnh, theo ngũ hành:</p>
<table>
<thead>
<tr><th>Mệnh</th><th>Màu tương hợp</th><th>Màu nên tránh</th><th>Gợi ý màu ngọc</th></tr>
</thead>
<tbody>
<tr><td><strong>Kim</strong></td><td>Trắng, xám, ghi (bản mệnh); vàng, nâu đất (Thổ sinh Kim)</td><td>Đỏ, hồng, tím (Hỏa khắc Kim)</td><td>Ngọc trắng, trắng băng; ngọc vàng (hoàng phỉ)</td></tr>
<tr><td><strong>Mộc</strong></td><td>Xanh lá (bản mệnh); đen, xanh dương (Thủy sinh Mộc)</td><td>Trắng, xám (Kim khắc Mộc)</td><td>Ngọc xanh lục; ngọc đen (mặc thúy)</td></tr>
<tr><td><strong>Thủy</strong></td><td>Đen, xanh dương (bản mệnh); trắng, xám (Kim sinh Thủy)</td><td>Vàng, nâu (Thổ khắc Thủy)</td><td>Ngọc đen (mặc thúy); ngọc trắng, trắng băng</td></tr>
<tr><td><strong>Hỏa</strong></td><td>Đỏ, hồng, tím (bản mệnh); xanh lá (Mộc sinh Hỏa)</td><td>Đen, xanh dương (Thủy khắc Hỏa)</td><td>Ngọc xanh lục; ngọc tím lavender; ngọc đỏ (hồng phỉ)</td></tr>
<tr><td><strong>Thổ</strong></td><td>Vàng, nâu đất (bản mệnh); đỏ, hồng, tím (Hỏa sinh Thổ)</td><td>Xanh lá (Mộc khắc Thổ)</td><td>Ngọc vàng (hoàng phỉ); ngọc tím lavender; ngọc đỏ (hồng phỉ)</td></tr>
</tbody>
</table>

<h2>Các màu ngọc phỉ thúy phổ biến và mệnh tương hợp</h2>
<h3>Ngọc xanh lục – hợp mệnh Mộc, Hỏa</h3>
<p>Xanh lục là sắc màu kinh điển và được ưa chuộng nhất của phỉ thúy, trải dài từ xanh nhạt, xanh táo đến xanh lục đậm. Sắc xanh lục tươi, đậm và đều màu, thường được gọi là “đế vương lục”, được xem là quý hiếm bậc nhất. Theo ngũ hành, xanh lục là màu bản mệnh của người mệnh Mộc và là màu tương sinh của người mệnh Hỏa (Mộc sinh Hỏa).</p>
<h3>Ngọc trắng, trắng băng – hợp mệnh Kim, Thủy</h3>
<p>Ngọc trắng mang vẻ thanh nhã, tinh khiết; những món có độ trong cao, nhìn như băng thường được gọi là ngọc trắng băng. Màu trắng thuộc hành Kim nên hợp với người mệnh Kim, đồng thời hợp với người mệnh Thủy theo quan hệ Kim sinh Thủy.</p>
<h3>Ngọc tím lavender – hợp mệnh Hỏa, Thổ</h3>
<p>Sắc tím oải hương nhẹ nhàng, nữ tính, ngày càng được nhiều bạn trẻ yêu thích. Theo ngũ hành, tím thuộc nhóm màu của hành Hỏa, nên hợp với người mệnh Hỏa và người mệnh Thổ (Hỏa sinh Thổ).</p>
<h3>Ngọc vàng (hoàng phỉ) – hợp mệnh Thổ, Kim</h3>
<p>Ngọc vàng có sắc ấm, từ vàng mật ong đến vàng nâu. Vàng là màu bản mệnh của hành Thổ, đồng thời là màu tương sinh cho người mệnh Kim (Thổ sinh Kim).</p>
<h3>Ngọc đen (mặc thúy) – hợp mệnh Thủy, Mộc</h3>
<p>Mặc thúy trông đen tuyền dưới ánh sáng thông thường, nhưng khi soi qua nguồn sáng mạnh, những phần mỏng có thể ánh lên sắc xanh lục đậm. Màu đen thuộc hành Thủy, hợp với người mệnh Thủy và người mệnh Mộc (Thủy sinh Mộc).</p>
<h3>Ngọc đỏ (hồng phỉ) – hợp mệnh Hỏa, Thổ</h3>
<p>Hồng phỉ có sắc đỏ cam đến đỏ nâu ấm áp, mang lại cảm giác rạng rỡ, ấm cúng. Màu đỏ thuộc hành Hỏa, hợp với người mệnh Hỏa và người mệnh Thổ (Hỏa sinh Thổ).</p>

<h2>Vài lưu ý khi chọn ngọc theo mệnh</h2>
<ul>
<li><strong>Ngọc nhiều màu:</strong> một món ngọc có thể có nhiều mảng màu đan xen, ví dụ nền trắng điểm xanh hoặc xanh lục pha tím. Khi đó, bạn có thể dựa vào màu chủ đạo của món ngọc để tham khảo.</li>
<li><strong>Màu “nên tránh” không phải điều cấm kỵ tuyệt đối:</strong> đây là lời khuyên mang tính tham khảo theo quan niệm phong thủy. Nếu thật sự yêu thích một món ngọc, bạn hoàn toàn có thể đeo.</li>
<li><strong>Chất lượng đặt lên trước:</strong> dù màu hợp mệnh đến đâu, hãy ưu tiên ngọc phỉ thúy tự nhiên loại A, có giấy kiểm định từ phòng kiểm định đá quý uy tín. Màu của ngọc đã nhuộm (loại C) là màu nhân tạo và có thể phai theo thời gian.</li>
</ul>

<h2>Sở thích và sự hài hòa khi đeo cũng quan trọng không kém</h2>
<p>Phong thủy là một góc nhìn để bạn tham khảo, nhưng một món trang sức đẹp còn cần hợp với chính người đeo: tông da, dáng tay, phong cách ăn mặc và hoàn cảnh sử dụng hằng ngày. Có người mệnh Kim lại thấy mình rạng rỡ nhất với chiếc vòng tím lavender; có người mệnh Mộc lại yêu nét thanh khiết của ngọc trắng băng. Cảm giác yêu thích và tự tin khi đeo luôn là điều đáng quý.</p>
<p>Nếu có thể, bạn hãy ướm thử món ngọc dưới ánh sáng tự nhiên và xem màu ngọc khi lên tay, lên cổ có hài hòa không. Khi phân vân giữa hai màu, hãy chọn món mà bạn muốn đeo mỗi ngày. Viên Ngọc Phương luôn sẵn lòng tư vấn, gửi thêm ảnh và video thực tế để bạn chọn được món ngọc vừa hợp mệnh, vừa thật sự hợp với mình.</p>
HTML,
    ],
    [
        'slug'    => 'cach-bao-quan-trang-suc-ngoc-phi-thuy',
        'title'   => 'Cách đeo và bảo quản trang sức ngọc phỉ thúy luôn bền đẹp',
        'date'    => '2026-09-24',
        'author'  => 'Viên Ngọc Phương',
        'image'   => 'products/chuoi-hat-ngoc-phi-thuy-xanh-8mm-2.svg',
        'excerpt' => 'Ngọc phỉ thúy rất bền nhưng vẫn có thể nứt vỡ khi va đập mạnh. Vài thói quen đeo, vệ sinh và cất giữ đơn giản sẽ giúp món ngọc của bạn luôn đẹp theo năm tháng.',
        'content' => <<<'HTML'
<p>Ngọc phỉ thúy có độ cứng khoảng 6,5–7 trên thang Mohs, cấu trúc gồm vô số tinh thể nhỏ đan xen chặt chẽ nên khá dai và bền. Nhờ vậy, một món ngọc được giữ gìn tốt có thể theo bạn nhiều năm, thậm chí trở thành kỷ vật trao lại cho con cháu. Tuy vậy, “bền” không có nghĩa là không thể hư hỏng: ngọc vẫn có thể nứt, mẻ hoặc vỡ khi va đập mạnh, và bề mặt có thể kém bóng nếu thường xuyên tiếp xúc hóa chất. Dưới đây là những thói quen đơn giản giúp trang sức ngọc của bạn luôn đẹp.</p>

<h2>Tránh va đập và bề mặt cứng</h2>
<p>Phần lớn sự cố với trang sức ngọc, nhất là vòng tay, đến từ những va chạm vô tình: gõ vào mặt bàn đá, lan can, bồn rửa, cửa kính hay rơi xuống sàn gạch. Bạn nên:</p>
<ul>
<li>Để ý khi đặt tay lên bàn, khi mở cửa xe, xách đồ hoặc gõ bàn phím.</li>
<li>Hạn chế đeo vòng ngọc sát cạnh vòng kim loại hay vòng đá cứng khác trên cùng một tay, để chúng không cọ và va vào nhau liên tục.</li>
<li>Khi tháo ra, đặt ngọc lên khăn mềm thay vì đặt trực tiếp lên mặt đá, mặt kính.</li>
</ul>

<h2>Tránh hóa chất</h2>
<p>Nước hoa, keo xịt tóc, kem dưỡng, nước rửa chén, bột giặt, chất tẩy rửa, nước tẩy có clo hay nước hồ bơi đều không có lợi cho trang sức. Hóa chất có thể làm mờ lớp sáp và độ bóng trên bề mặt ngọc, khiến dây xâu nhanh mục, làm phần vàng, bạc gắn kèm bị xỉn màu hoặc hư hại. Một nguyên tắc dễ nhớ: <strong>trang sức đeo vào sau cùng, tháo ra đầu tiên</strong> – tức là đeo ngọc sau khi đã trang điểm, xịt nước hoa xong, và tháo ngọc trước khi tắm, rửa mặt hay thoa kem.</p>

<h2>Tránh nhiệt độ thay đổi đột ngột</h2>
<p>Đừng để ngọc gần bếp lửa, ngâm trong nước sôi hay để lâu trong xe ô tô dưới trời nắng gắt. Nhiệt độ cao hoặc chuyển từ nóng sang lạnh quá nhanh có thể làm các vết rạn sẵn có trong ngọc lan rộng, đồng thời ảnh hưởng đến dây xâu và lớp sáp bề mặt.</p>

<h2>Khi nào nên tháo trang sức ngọc ra?</h2>
<ul>
<li>Khi tập thể dục, chơi thể thao, tập gym hoặc đi bơi.</li>
<li>Khi làm việc nhà: rửa bát, giặt giũ, lau dọn, nấu nướng.</li>
<li>Khi làm vườn, sửa chữa đồ đạc hay khuân vác đồ nặng.</li>
<li>Khi tắm, xông hơi hoặc đi ngủ – đặc biệt với chuỗi hạt, để dây không bị kéo căng hay vướng víu.</li>
<li>Khi thoa kem, xịt nước hoa hay dùng mỹ phẩm.</li>
</ul>
<p>Riêng với vòng tay ngọc nguyên khối đeo vừa khít, việc tháo ra đeo vào thường xuyên đôi khi lại dễ gây rơi vỡ hơn. Trong trường hợp này, bạn có thể giữ vòng trên tay nhưng cẩn thận hơn khi làm việc và tránh để vòng tiếp xúc với hóa chất.</p>

<h2>Cách vệ sinh ngọc phỉ thúy tại nhà</h2>
<p>Bụi, mồ hôi và kem dưỡng bám lâu ngày có thể khiến ngọc kém trong và kém bóng. Bạn có thể vệ sinh định kỳ theo các bước sau:</p>
<ol>
<li>Pha một ít nước ấm (không nóng) với vài giọt xà phòng dịu nhẹ, không chứa chất tẩy mạnh.</li>
<li>Dùng khăn mềm nhúng nước xà phòng lau nhẹ bề mặt ngọc. Với các chi tiết chạm khắc, có thể dùng bàn chải lông thật mềm.</li>
<li>Tráng lại bằng nước sạch để loại bỏ hết xà phòng.</li>
<li>Lau khô ngay bằng khăn mềm, không xơ và để ngọc khô hoàn toàn trước khi cất.</li>
</ol>
<p>Với chuỗi hạt và mặt dây chuyền xâu dây, bạn không nên ngâm cả chuỗi trong nước vì dây có thể bị giãn, yếu đi và lâu khô. Hãy lau từng hạt bằng khăn ẩm rồi lau lại bằng khăn khô.</p>
<h3>Không dùng máy rửa siêu âm hoặc máy hơi nước</h3>
<p>Máy rửa siêu âm và máy phun hơi nước nóng thường được dùng để làm sạch trang sức kim loại, nhưng không phù hợp với ngọc đã qua xử lý hay ngọc gắn trên khung kim loại. Rung động mạnh và nhiệt độ cao có thể làm vết rạn sẵn có lan rộng, làm hỏng lớp nhựa trong ngọc đã xử lý, làm lỏng ổ chấu giữ ngọc và làm hỏng dây xâu. Cách an toàn nhất cho mọi món ngọc vẫn là rửa tay nhẹ nhàng như hướng dẫn ở trên.</p>

<h2>Cất giữ đúng cách</h2>
<ul>
<li>Mỗi món ngọc nên được cất riêng trong túi vải mềm hoặc ngăn hộp có lót nhung.</li>
<li>Không để chung với kim cương, sapphire, ruby – những loại đá cứng hơn ngọc có thể làm xước bề mặt ngọc. Ngược lại, ngọc cũng có thể làm xước ngọc trai, đồ vàng, bạc nếu cọ xát vào nhau.</li>
<li>Bụi thông thường có thể chứa hạt thạch anh, cứng tương đương hoặc hơn ngọc. Vì vậy, đừng chà ngọc đang bám bụi bằng khăn khô; hãy rửa sạch trước.</li>
<li>Cất ở nơi khô ráo, tránh ánh nắng trực tiếp và nguồn nhiệt.</li>
<li>Chuỗi hạt nên được đặt nằm phẳng thay vì treo lâu ngày, để dây không bị kéo giãn.</li>
</ul>

<h2>Kiểm tra định kỳ</h2>
<p>Vài tháng một lần, bạn hãy dành chút thời gian kiểm tra trang sức ngọc của mình:</p>
<ul>
<li>Dây xâu chuỗi hạt, dây đeo mặt có bị sờn, giãn hay bạc màu không.</li>
<li>Ổ chấu, móc khóa, khung kim loại có bị lỏng, cong hoặc mòn không.</li>
<li>Bề mặt ngọc có vết mẻ hay vết rạn mới sau những lần va chạm không.</li>
</ul>
<p>Nếu thấy dấu hiệu bất thường, hãy tạm ngưng đeo và mang sản phẩm đến cửa hàng để được kiểm tra. Với sản phẩm mua tại Viên Ngọc Phương, bạn được làm sạch, đánh bóng miễn phí trọn đời và được miễn phí xâu lại, thay dây cho chuỗi hạt, mặt dây chuyền theo chính sách bảo hành. Chăm sóc đúng cách hôm nay chính là cách giữ trọn vẻ đẹp của ngọc cho nhiều năm sau.</p>
HTML,
    ],
    [
        'slug'    => 'huong-dan-do-ni-tay-chon-vong-ngoc',
        'title'   => 'Hướng dẫn đo ni tay để chọn vòng ngọc vừa vặn',
        'date'    => '2026-09-10',
        'author'  => 'Viên Ngọc Phương',
        'image'   => 'products/vong-ngoc-phi-thuy-xanh-luc-de-vuong-2.svg',
        'excerpt' => 'Cách đo chu vi bàn tay, quy đổi sang ni vòng và mẹo đeo vòng ngọc vào tay nhẹ nhàng, an toàn – kèm bảng tra cứu nhanh để bạn tự chọn size tại nhà.',
        'content' => <<<'HTML'
<p>Vòng ngọc phỉ thúy nguyên khối được chế tác từ một khối ngọc liền, không có khóa và không thể co giãn hay nới rộng như vòng kim loại. Vì vậy, chọn đúng kích thước – thường gọi là “ni” hay “size” vòng – là bước rất quan trọng: vòng quá nhỏ thì không đeo vào được hoặc đeo rất khó, vòng quá rộng lại dễ tuột và va đập. Bài viết này hướng dẫn bạn tự đo ni tay tại nhà chỉ trong vài phút.</p>

<h2>Ni vòng là gì?</h2>
<p>Ni vòng là <strong>đường kính trong</strong> của chiếc vòng, tính bằng milimét (mm). Ví dụ, vòng ni 56 nghĩa là khoảng cách bên trong lòng vòng là khoảng 56 mm. Điều cần nhớ: chiếc vòng phải luồn qua được phần rộng nhất của bàn tay, nên ta đo <strong>bàn tay</strong> chứ không đo cổ tay.</p>

<h2>Chuẩn bị dụng cụ</h2>
<ul>
<li>Một thước dây mềm (loại dùng để may đo).</li>
<li>Nếu không có thước dây: một sợi chỉ không co giãn hoặc một dải giấy nhỏ, cùng thước kẻ và bút.</li>
</ul>

<h2>Các bước đo ni tay</h2>
<ol>
<li>Chọn bàn tay bạn định đeo vòng. Hai bàn tay có thể chênh nhau đôi chút, nên hãy đo đúng tay sẽ đeo.</li>
<li>Khép các ngón tay lại, gập ngón cái vào lòng bàn tay sao cho đầu ngón cái chạm tới ngón út – đây chính là tư thế bàn tay khi luồn qua vòng.</li>
<li>Quấn thước dây (hoặc sợi chỉ, dải giấy) quanh phần rộng nhất của bàn tay, thường là ngang qua các khớp ngón tay và phần gốc ngón cái. Thước nên ôm sát nhưng không siết chặt.</li>
<li>Đọc số đo chu vi theo centimét. Nếu dùng chỉ hoặc giấy, đánh dấu điểm giao nhau rồi trải thẳng ra, đo bằng thước kẻ.</li>
<li>Đo lại 2–3 lần để chắc chắn kết quả ổn định.</li>
<li>Tính ni vòng theo công thức: <strong>Ni (mm) ≈ Chu vi (mm) ÷ 3,14</strong>.</li>
</ol>
<p>Ví dụ: chu vi bàn tay khi chụm là 17,5 cm, tức 175 mm. Ta có 175 ÷ 3,14 ≈ 55,7, làm tròn thành khoảng <strong>56 mm</strong>.</p>
<p>Bạn nên đo khi tay ở trạng thái bình thường, không ngay sau khi vận động mạnh, ngâm nước nóng hay lúc tay đang lạnh cóng, vì bàn tay có thể hơi nở ra hoặc co lại.</p>

<h2>Bảng quy đổi chu vi bàn tay sang ni vòng</h2>
<p>Để tiện tra cứu, bạn có thể dùng bảng dưới đây (kết quả đã làm tròn đến milimét gần nhất):</p>
<table>
<thead>
<tr><th>Chu vi bàn tay (cm)</th><th>Ni vòng tương ứng (mm)</th></tr>
</thead>
<tbody>
<tr><td>15,5</td><td>49</td></tr>
<tr><td>16,0</td><td>51</td></tr>
<tr><td>16,5</td><td>53</td></tr>
<tr><td>17,0</td><td>54</td></tr>
<tr><td>17,5</td><td>56</td></tr>
<tr><td>18,0</td><td>57</td></tr>
<tr><td>18,5</td><td>59</td></tr>
<tr><td>19,0</td><td>61</td></tr>
<tr><td>19,5</td><td>62</td></tr>
<tr><td>20,0</td><td>64</td></tr>
</tbody>
</table>

<h2>Nên chọn ni bằng hay lớn hơn số đo?</h2>
<p>Sau khi có kết quả, bạn nên chọn vòng có ni <strong>bằng hoặc lớn hơn 1–2 mm</strong> so với con số tính được, để đeo vào dễ hơn và vẫn thoải mái khi tay hơi nở lúc trời nóng. Một vài gợi ý:</p>
<ul>
<li><strong>Vòng ni lớn hơn</strong> dễ đeo vào, tháo ra hơn, nhưng sẽ lỏng trên cổ tay, xoay và trượt nhiều, dễ tuột hoặc va đập vào đồ vật xung quanh.</li>
<li><strong>Vòng vừa khít</strong> ôm tay gọn gàng, ít va chạm hơn, nhưng thao tác đeo và tháo sẽ khó hơn.</li>
<li>Với vòng bản to hoặc dày, cảm giác khi đeo thường chật hơn vòng mảnh cùng ni, bạn có thể cân nhắc chọn rộng hơn một chút.</li>
<li>Nếu cổ tay bạn nhỏ nhưng khớp tay to, vòng vừa qua bàn tay sẽ khá rộng ở cổ tay – điều này hoàn toàn bình thường với vòng nguyên khối.</li>
</ul>

<h2>Mẹo đeo vòng ngọc vào tay an toàn</h2>
<p>Đeo vòng ngọc lần đầu, đặc biệt là vòng vừa khít, cần một chút kiên nhẫn. Bạn hãy làm theo các mẹo sau:</p>
<ul>
<li><strong>Chuẩn bị chỗ ngồi an toàn:</strong> ngồi xuống và thao tác phía trên giường, gối hoặc khăn dày gấp nhiều lớp, để lỡ vòng tuột tay cũng không rơi xuống nền cứng.</li>
<li><strong>Làm trơn bàn tay:</strong> thoa một lớp kem dưỡng da hoặc nước xà phòng loãng lên bàn tay để vòng trượt dễ hơn.</li>
<li><strong>Mẹo túi nilon:</strong> trùm một chiếc túi nilon mỏng lên bàn tay, luồn vòng qua bên ngoài túi rồi đẩy từ từ. Lớp túi trơn giúp vòng không kéo da; khi vòng đã qua khớp tay, nhẹ nhàng rút túi ra.</li>
<li><strong>Chụm tay đúng tư thế:</strong> chụm tay như lúc đo, đưa vòng qua khớp ngón cái trước, sau đó vừa xoay nhẹ vừa đẩy vòng qua các khớp ngón tay.</li>
<li><strong>Không cố quá sức:</strong> nếu thấy đau hoặc vòng không thể qua được khớp tay, hãy dừng lại. Có thể chiếc vòng đang quá nhỏ so với tay bạn.</li>
</ul>
<p>Khi tháo vòng, bạn cũng làm tương tự theo chiều ngược lại và luôn giữ tay phía trên bề mặt mềm.</p>
<p>Nếu vẫn chưa chắc chắn về kích thước, bạn có thể gửi số đo chu vi bàn tay cho Viên Ngọc Phương. Chúng tôi sẽ tư vấn ni vòng phù hợp và hỗ trợ đổi size theo chính sách đổi trả của cửa hàng, tùy theo mẫu vòng còn sẵn.</p>
HTML,
    ],
    [
        'slug'    => 'ngoc-phi-thuy-loai-a-b-c-la-gi',
        'title'   => 'Ngọc phỉ thúy loại A, B, C là gì? Cách nhận biết khi mua',
        'date'    => '2026-08-28',
        'author'  => 'Viên Ngọc Phương',
        'image'   => 'products/vong-ngoc-phi-thuy-trang-bang-2.svg',
        'excerpt' => 'Ngọc loại A là ngọc tự nhiên, còn loại B, C đã qua tẩy axit, bơm nhựa hoặc nhuộm màu. Tìm hiểu sự khác biệt và cách mua ngọc phỉ thúy thật an tâm.',
        'content' => <<<'HTML'
<p>Khi tìm hiểu về ngọc phỉ thúy, bạn sẽ thường nghe đến các khái niệm “ngọc loại A”, “loại B”, “loại C”. Đây là cách phân loại phổ biến trong giới kinh doanh và kiểm định đá quý, cho biết món ngọc là tự nhiên hay đã qua xử lý. Hiểu rõ điều này giúp bạn tránh mua nhầm và chi tiền đúng với giá trị thật.</p>

<h2>Ngọc phỉ thúy là gì?</h2>
<p>Ngọc phỉ thúy (jadeite) là một khoáng vật thuộc nhóm pyroxen, có độ cứng khoảng 6,5–7 trên thang Mohs, tỷ trọng khoảng 3,33 và chiết suất khoảng 1,66. Phần lớn ngọc phỉ thúy đạt chất lượng đá quý trên thị trường có nguồn gốc từ Myanmar.</p>
<p>Cần phân biệt phỉ thúy với nephrite (thường được gọi là ngọc bích nephrite). Trong tiếng Anh, cả hai đều được gọi chung là “jade”, nhưng đây là hai khoáng vật khác nhau: nephrite thuộc nhóm amphibol, nhẹ hơn (tỷ trọng khoảng 2,95) và có chiết suất thấp hơn (khoảng 1,61–1,62). Cách phân loại A, B, C dưới đây được dùng chủ yếu cho ngọc phỉ thúy.</p>

<h2>Phân loại A, B, C dựa trên điều gì?</h2>
<p>Các “loại” A, B, C <strong>không phải là cấp chất lượng</strong> đẹp hay xấu, mà cho biết ngọc đã được xử lý như thế nào sau khi khai thác.</p>
<h3>Ngọc loại A – tự nhiên</h3>
<p>Ngọc loại A là ngọc tự nhiên, chỉ trải qua các công đoạn cắt, mài, đánh bóng, làm sạch và có thể được phủ một lớp sáp mỏng trên bề mặt theo phương pháp truyền thống. Màu sắc, độ trong và cấu trúc bên trong hoàn toàn do tự nhiên tạo nên. Lưu ý rằng ngọc loại A vẫn có rất nhiều mức chất lượng khác nhau, tùy màu sắc, độ trong, độ mịn và tay nghề chế tác.</p>
<h3>Ngọc loại B – tẩy axit và bơm nhựa</h3>
<p>Ngọc loại B được ngâm trong axit để tẩy đi các vết bẩn, vết ố vàng nâu, sau đó được bơm nhựa (polymer) vào các khe hở giữa các tinh thể để lấp đầy, giúp ngọc trông trong và sạch hơn. Đổi lại, cấu trúc ngọc bị tổn thương và lớp nhựa có thể lão hóa theo thời gian.</p>
<h3>Ngọc loại C – nhuộm màu</h3>
<p>Ngọc loại C được nhuộm màu nhân tạo để tạo ra hoặc làm đậm màu, thường là xanh lục hoặc tím. Màu nhuộm không bền và có thể phai theo thời gian.</p>
<h3>Ngọc loại B+C</h3>
<p>Đây là ngọc vừa được tẩy axit, bơm nhựa, vừa được nhuộm màu – mang đồng thời nhược điểm của cả hai loại trên.</p>

<h2>Bảng so sánh nhanh</h2>
<table>
<thead>
<tr><th>Loại</th><th>Cách xử lý</th><th>Màu sắc</th><th>Độ bền theo thời gian</th><th>Giá trị</th></tr>
</thead>
<tbody>
<tr><td><strong>A</strong></td><td>Chỉ cắt mài, làm sạch, đánh bóng, có thể phủ sáp</td><td>Tự nhiên</td><td>Ổn định, bền lâu</td><td>Giữ giá trị, tùy chất lượng ngọc</td></tr>
<tr><td><strong>B</strong></td><td>Tẩy axit, bơm nhựa</td><td>Màu tự nhiên nhưng nền được “làm sạch” nhân tạo</td><td>Giòn hơn; nhựa có thể ngả vàng, mờ đục</td><td>Thấp</td></tr>
<tr><td><strong>C</strong></td><td>Nhuộm màu</td><td>Nhân tạo</td><td>Màu có thể phai, đổi sắc</td><td>Thấp</td></tr>
<tr><td><strong>B+C</strong></td><td>Tẩy axit, bơm nhựa và nhuộm màu</td><td>Nhân tạo</td><td>Mang nhược điểm của cả B và C</td><td>Thấp</td></tr>
</tbody>
</table>

<h2>Có thể tự nhận biết bằng mắt thường không?</h2>
<p>Trên mạng có nhiều “mẹo” thử ngọc tại nhà, nhưng thực tế <strong>những cách này không đáng tin cậy</strong>:</p>
<ul>
<li><strong>Tiếng gõ</strong> phụ thuộc vào hình dáng, độ dày của món đồ và cách gõ; ngọc đã xử lý vẫn có thể cho tiếng kêu khá trong.</li>
<li><strong>Cảm giác mát tay</strong> có ở nhiều loại đá và cả thủy tinh, không giúp phân biệt ngọc tự nhiên với ngọc đã xử lý.</li>
<li><strong>Cào xước</strong> chỉ cho biết vật liệu cứng hay mềm, không phát hiện được việc tẩy axit hay nhuộm màu, lại có thể làm hỏng món đồ.</li>
<li><strong>Hơ lửa, đốt nóng</strong> có thể làm ngọc nứt vỡ – tuyệt đối không nên thử.</li>
</ul>
<p>Người có kinh nghiệm có thể nhận ra một số dấu hiệu nghi vấn khi soi kỹ bằng kính lúp, như bề mặt có những rãnh li ti dạng mạng nhện ở ngọc tẩy axit, hay màu tập trung dọc theo các khe nứt nhỏ ở ngọc nhuộm. Tuy nhiên, các dấu hiệu này chỉ mang tính gợi ý. Ngoài ra, trên thị trường còn có những vật liệu giả ngọc như thủy tinh, thạch anh nhuộm màu hay serpentin.</p>
<p>Để kết luận chắc chắn, món ngọc cần được kiểm tra tại <strong>phòng kiểm định đá quý uy tín</strong>, nơi chuyên viên quan sát dưới kính hiển vi phóng đại, đo chiết suất, tỷ trọng để xác định có đúng là phỉ thúy hay không, và dùng các phương pháp quang phổ như quang phổ hồng ngoại (FTIR) để phát hiện nhựa, quang phổ hấp thụ để phát hiện chất nhuộm.</p>

<h2>Lời khuyên khi mua ngọc phỉ thúy</h2>
<ul>
<li><strong>Chọn người bán cung cấp giấy kiểm định</strong> từ một phòng kiểm định đá quý uy tín, ghi rõ là ngọc phỉ thúy (jadeite) tự nhiên, không xử lý hoặc loại A. Hãy đối chiếu ảnh, kích thước, trọng lượng trên giấy với món ngọc thực tế; nếu giấy có mã tra cứu, hãy tra cứu qua kênh chính thức của đơn vị kiểm định.</li>
<li><strong>Yêu cầu cam kết bằng văn bản:</strong> hóa đơn hoặc phiếu bảo hành nên ghi rõ sản phẩm là ngọc phỉ thúy tự nhiên loại A, kèm cách xử lý (như hoàn tiền) nếu kết quả kiểm định khác với cam kết.</li>
<li><strong>Cảnh giác với giá rẻ bất thường:</strong> ngọc loại A có màu đẹp, độ trong cao vốn hiếm và có giá tương xứng. Một món ngọc vừa xanh vừa trong mà giá rẻ hơn hẳn mặt bằng chung rất có thể là ngọc đã qua xử lý hoặc hàng giả.</li>
<li><strong>Không dựa vào phép thử dân gian</strong> do người bán trình diễn để tin rằng ngọc là thật.</li>
<li><strong>Khi còn băn khoăn,</strong> bạn có thể mang món ngọc đến một phòng kiểm định đá quý uy tín để kiểm tra độc lập trước khi quyết định.</li>
</ul>

<h2>Vì sao ngọc loại B, C mất giá trị?</h2>
<p>Giá trị của ngọc phỉ thúy đến từ vẻ đẹp tự nhiên, độ hiếm và sự bền bỉ theo thời gian. Ngọc loại B và C đánh mất phần lớn những yếu tố đó:</p>
<ul>
<li>Tẩy axit làm cấu trúc tinh thể bị tổn thương, khiến ngọc giòn hơn và dễ nứt vỡ khi va chạm.</li>
<li>Lớp nhựa bơm vào có thể lão hóa, khiến ngọc ngả vàng, mờ đục và mất độ bóng.</li>
<li>Màu nhuộm có thể phai hoặc đổi sắc, nhất là khi gặp nắng, nhiệt và hóa chất.</li>
<li>Vẻ đẹp nhìn thấy không còn hoàn toàn tự nhiên, nên khó giữ giá và rất khó bán lại.</li>
</ul>
<p>Tại Viên Ngọc Phương, mỗi món trang sức ngọc phỉ thúy đều kèm giấy kiểm định và được cam kết là ngọc tự nhiên loại A. Nếu một phòng kiểm định đá quý uy tín xác nhận điều ngược lại, chúng tôi hoàn lại toàn bộ số tiền theo chính sách bảo hành. Bởi chúng tôi tin rằng một món ngọc chỉ thật sự quý khi bạn hoàn toàn an tâm về nguồn gốc của nó.</p>
HTML,
    ],
];
