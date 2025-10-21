# Test file to verify the chatbot application structure
import os

def check_structure():
    """Check if all required files and directories exist"""
    required_dirs = [
        'chatbot_app',
        'chatbot_app/static',
        'chatbot_app/templates'
    ]
    
    required_files = [
        'chatbot_app/app.py',
        'chatbot_app/requirements.txt',
        'chatbot_app/README.md',
        'chatbot_app/templates/index.html',
        'chatbot_app/static/styles.css',
        'chatbot_app/static/script.js'
    ]
    
    print("Verifying chatbot application structure...")
    
    all_good = True
    
    for directory in required_dirs:
        if os.path.isdir(directory):
            print(f"✓ Directory {directory} exists")
        else:
            print(f"✗ Directory {directory} missing")
            all_good = False
    
    for file in required_files:
        if os.path.isfile(file):
            print(f"✓ File {file} exists")
        else:
            print(f"✗ File {file} missing")
            all_good = False
    
    if all_good:
        print("\n✓ All required files and directories are in place!")
        print("\nThe chatbot application is ready. To run it:")
        print("1. Navigate to the chatbot_app directory")
        print("2. Install dependencies: pip install -r requirements.txt")
        print("3. Run the application: python app.py")
        print("4. Open your browser at http://localhost:5000")
    else:
        print("\n✗ Some files or directories are missing!")

if __name__ == "__main__":
    check_structure()