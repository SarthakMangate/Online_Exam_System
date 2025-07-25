#  ProctorAI - AI-powered Online Exam Proctoring System

**ProctorAI** is a secure online examination platform that uses AI to detect cheating (tab switching, multiple faces, inactivity).  
Built with PHP, MySQL, Bootstrap, and Python for real-time proctoring.

>  **Live demo**: [exam-secure-platform.infinityfreeapp.com](https://exam-secure-platform.infinityfreeapp.com)

---

##  Features
✅ AI-based webcam monitoring  
✅ Detects multiple faces, tab switching & inactivity  
✅ Role-based dashboards: Student, Teacher, Admin  
✅ Malpractice logs visible to admin  
✅ Admin can approve students to retake test if false flagged  
✅ Clean, responsive UI (Bootstrap)  
✅ Passwords stored securely (hashed)

>  If you were caught but it’s unfair, don’t worry!  
> Admin can check your malpractice logs and allow you to appear for the test again if it was a system fault.

---

## 🛠️ Tech Stack
- Frontend: **HTML5, CSS3, Bootstrap 5, JavaScript**
- Backend: **PHP**
- Database: **MySQL**
- AI Proctoring: **Python (OpenCV)**

---

##  Installation & Setup

✅ Clone the repository
Bash

git clone https://github.com/SarthakMangate/Online_Exam_System.git
cd Online_Exam_System

✅ Install Python dependencies
pip install opencv-python
pip install numpy

✅ Import database
- Open phpMyAdmin
- Create a new database (e.g., proctorai)
- Import proctorai.sql from the project folder

✅ Configure database connection

Edit /db/db.php:

$servername = "localhost";

$username = "root";

$password = "";

$dbname = "proctorai";

 Run locally
- Place the project folder inside your XAMPP htdocs directory
- Start Apache and MySQL from XAMPP control panel
(Optional, for AI proctoring):
python app.py

 Open in browser
http://localhost/Online_Exam_System

 Usage notes
- Students & teachers can register and login
- Admin cannot register; use pre-created admin credentials
- Admin dashboard shows malpractice logs; admin can reset / approve student for retest

 Contributing
- Fork this repo
- Create a new branch:
git checkout -b feature/your-feature
- Make changes & commit:
git commit -m "Add new feature"
- Push:
git push origin feature/your-feature
- Open a Pull Request on GitHub

📧 Contact

Email: sarthakmangate17@gmail.com

LinkedIn: https://www.linkedin.com/in/sarthak-mangate-99b104271/
