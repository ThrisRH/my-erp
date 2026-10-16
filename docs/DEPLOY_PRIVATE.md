# Dùng My-ERP trên điện thoại qua Oracle Cloud và Tailscale

Đây là cách chạy app và MySQL trên một Oracle VM, còn điện thoại truy cập qua mạng riêng Tailscale. Máy tính cá nhân không cần bật. URL HTTPS của Tailscale chỉ mở được trên thiết bị đã tham gia cùng tailnet; không cần mua tên miền. **Không dùng Tailscale Funnel hoặc mở cổng 8000/3306 ra Internet.**

## 1. Tạo tài khoản

1. Tạo [Oracle Cloud Free Tier](https://www.oracle.com/cloud/free/) bằng thông tin và thẻ của chính bạn. Chọn home region có Always Free Ampere A1; region này không đổi được sau khi tạo tài khoản. Chỉ chọn tài nguyên có nhãn **Always Free**.
2. Tạo [Tailscale Personal](https://tailscale.com/pricing) và cài Tailscale trên điện thoại. Đăng nhập điện thoại vào tailnet của bạn.
3. Tạo một Oracle VM Always Free chạy Ubuntu/Oracle Linux. Nếu Ampere A1 hết chỗ ở region đó, cần chờ tài nguyên khả dụng. Cài Docker Engine và Docker Compose theo tài liệu chính thức của Docker.

## 2. Chạy app trên VM

Clone project lên VM, lấy phiên bản code có file `docker-compose.private.yml`, rồi trong thư mục project:

```sh
cp .env.example .env
```

Sửa `.env` trên VM: đặt mật khẩu `MYSQL_PASSWORD` và `MYSQL_ROOT_PASSWORD` ngẫu nhiên, dài, khác nhau; đặt `APP_BASE_URL` thành URL HTTPS Tailscale của VM; không đưa file này lên Git. Khi có dịch vụ SMTP, đặt `MAIL_TRANSPORT=smtp` và điền các biến `SMTP_*` theo [hướng dẫn tài khoản](ACCESS_CONTROL.md). Chưa có SMTP thì đăng ký và quên mật khẩu sẽ tạm không hoạt động để tránh lộ link đặt lại trên web. Sau đó:

```sh
docker compose -f docker-compose.private.yml up -d --build
docker compose -f docker-compose.private.yml ps
curl --fail http://127.0.0.1:8000/personal-finance
```

Compose riêng này chỉ bind web vào `127.0.0.1:8000` của VM (có thể đổi qua `PRIVATE_APP_PORT` khi cần). MySQL không publish port. Schema từ `docker/mysql/init/` chỉ được MySQL áp dụng khi volume database còn trống; không xóa volume để cập nhật schema. Với database đã có dữ liệu, áp dụng migration ACL và recovery theo [ACCESS_CONTROL.md](ACCESS_CONTROL.md) và khởi tạo admin trước khi đăng nhập.

## 3. Cho điện thoại truy cập

Cài Tailscale trên VM theo [hướng dẫn Linux](https://tailscale.com/docs/install/linux), đăng nhập VM vào cùng tailnet với điện thoại, rồi chạy trên VM:

```sh
tailscale serve --bg 8000
tailscale serve status
```

Lệnh thứ hai cho biết URL HTTPS `*.ts.net`. Mở URL đó trên điện thoại khi Tailscale đã kết nối. [Tailscale Serve](https://tailscale.com/docs/reference/tailscale-cli/serve) giữ cấu hình sau khi VM khởi động lại khi dùng `--bg`.

## 4. Dữ liệu và giới hạn hiện tại

- Database là volume Docker trên Oracle VM. Hãy xuất bản sao lưu định kỳ và cất ở nơi khác VM; không coi một VM miễn phí là bản sao lưu.
- App có đăng nhập và ACL; tạo tài khoản admin trước khi sử dụng. Chỉ đưa thiết bị tin cậy vào tailnet.
- Bộ dependency hiện khóa PHP 8.0/8.1 và image riêng này dùng PHP 8.0. Đây là cấu hình truy cập riêng tạm thời. Cần nâng PHP/Laminas và rà soát bảo mật trước khi mở truy cập công khai.
- Oracle có thể thiếu Always Free VM ở region đã chọn và có chính sách thu hồi VM nhàn rỗi. Theo dõi trạng thái VM và duy trì bản sao lưu độc lập.
