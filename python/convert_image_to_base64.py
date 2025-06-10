import base64

image_path = r"C:\xampp\htdocs\Online_Exam_System\images\image.jpg"
with open(image_path, "rb") as f:
    encoded = base64.b64encode(f.read()).decode('utf-8')
print(encoded)
