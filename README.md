# LOLCHESS 웹사이트

PHP로 제작한 롤토체스 추천 메타 사이트입니다. Oracle DB와 연동되어 작동합니다.

## 📁 프로젝트 구성

- `index.php`: 메인 페이지
- `db_connect.php`: DB 연결 (업로드하지 말고 `db_connect.sample.php` 참고)
- `main.css`: 스타일
- `images/`: 이미지 폴더 (필요 시)
- `추천메타전체보기.php`: 전체 메타 출력 페이지

## 🔌 Oracle DB 연동 방법

1. `db_connect.php`를 복사해서
2. 아래와 같이 본인의 Oracle DB 정보로 수정합니다.


🛠️ 실행 방법
XAMPP나 php-dev 서버 등으로 PHP 실행

웹 브라우저에서 index.php 실행

Oracle DB 연동 시 추천 메타 정보를 가져와 표시됩니다.
