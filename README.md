# 🏖️ Iceland Beach Resort
<div align="center">

![Iceland Beach Resort Banner](https://icelandbeach.com/static/images/img%20(1).png)

</div>

> A luxury beachfront escape offering serene views, sandy shorelines, and a vibrant coastal atmosphere in Lagos, Nigeria.

## 📋 Overview

Iceland Beach Resort is a modern, responsive web application showcasing a premium beachfront destination. The platform provides visitors with an immersive experience through stunning visuals, comprehensive resort information, and an intuitive booking interface. Built with modern web technologies and deployed with automated CI/CD pipelines for seamless updates.

## ✨ Key Features

- **🎥 Video Hero Section**: Captivating full-screen video background showcasing the resort's ambiance
- **📱 Fully Responsive Design**: Optimized viewing experience across all devices (mobile, tablet, desktop)
- **🖼️ Interactive Gallery**: Dynamic image gallery highlighting resort amenities and scenic views
- **🎯 Modern UI/UX**: Clean, intuitive interface built with Bootstrap 5.3.2
- **⚡ Fast Loading**: Optimized assets and efficient code structure for superior performance
- **🛠️ Admin Dashboard**: Comprehensive backend management system for content updates
- **🔒 Secure Architecture**: PHP-based backend with organized template structure
- **🚀 Automated Deployment**: CI/CD pipeline for instant updates to production

## 🛠️ Technologies Used

### Frontend
- **HTML5**: Semantic markup for better SEO and accessibility
- **CSS3**: Modern styling with custom animations and transitions
- **JavaScript (ES6+)**: Interactive features and dynamic content
- **Bootstrap 5.3.2**: Responsive grid system and pre-built components

### Backend
- **PHP**: Server-side scripting and business logic
- **Template Engine**: Custom template system in `/temoke` directory
- **Modular Components**: Reusable includes for headers, footers, and shared elements

### DevOps & Deployment
- **GitHub Actions**: Automated CI/CD workflow
- **SSH Deployment**: Secure file transfer to production server
- **Hostinger**: Linux-based hosting environment

## 📁 Project Structure

```
iceland-beach-resort/
├── admin/                  # Admin dashboard and management tools
├── includes/               # Reusable PHP components
│   ├── header.php         # Site header template
│   └── footer.php         # Site footer template
├── temoke/                # Template files and layouts
├── static/                # Static assets
│   ├── images/            # Image resources
│   ├── videos/            # Video content
│   ├── styles/            # CSS stylesheets
│   └── scripts/           # JavaScript files
├── .github/
│   └── workflows/         # GitHub Actions CI/CD configuration
├── index.php              # Homepage
└── README.md              # Project documentation
```

## 🚀 Getting Started

### Prerequisites

Before you begin, ensure you have the following installed:
- PHP 7.4 or higher
- A local web server (Apache/Nginx) or PHP built-in server
- Git
- A modern web browser

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/Manlikenonso1k/iceland-beach.git
   cd iceland-beach-resort
   ```

2. **Set up your local environment**
   ```bash
   # If using PHP built-in server
   php -S localhost:8000
   ```

3. **Access the application**
   
   Open your browser and navigate to:
   ```
   http://localhost:8000
   ```

4. **Configure environment variables** (if applicable)
   
   Create a configuration file for your local environment settings.

### Database Setup (if applicable)

If your project uses a database, include setup instructions here:
```sql
-- Import the database schema
-- Add relevant SQL commands or migration instructions
```

## 🔄 Deployment

This project uses **GitHub Actions** for automated deployment to Hostinger. Every push to the `main` branch triggers the CI/CD pipeline.

### Deployment Workflow

1. Code is pushed to the `main` branch
2. GitHub Actions workflow is triggered automatically
3. Files are deployed via SSH to the Hostinger server
4. Changes are live instantly

### Manual Deployment

If you need to deploy manually:
```bash
# Connect to your server via SSH
ssh user@your-hostinger-server.com

# Navigate to project directory and pull latest changes
cd /path/to/project
git pull origin main
```

## 🖼️ Screenshots

### Homepage
![Homepage Screenshot](./static/images/screenshot-home.jpg)
*Stunning hero section with video background*

### Gallery
![Gallery Screenshot](./static/images/screenshot-gallery.jpg)
*Interactive photo gallery showcasing resort features*

### Mobile View
![Mobile Screenshot](./static/images/screenshot-mobile.jpg)
*Fully responsive design on mobile devices*

## 🤝 Contributing

Contributions are welcome! If you'd like to improve Iceland Beach Resort, please follow these steps:

1. **Fork the repository**
   
   Click the "Fork" button at the top right of this page.

2. **Create a feature branch**
   ```bash
   git checkout -b feature/AmazingFeature
   ```

3. **Commit your changes**
   ```bash
   git commit -m 'Add some AmazingFeature'
   ```

4. **Push to the branch**
   ```bash
   git push origin feature/AmazingFeature
   ```

5. **Open a Pull Request**
   
   Navigate to your fork on GitHub and click "New Pull Request"

### Coding Standards

- Follow PSR-12 coding standards for PHP
- Use meaningful variable and function names
- Comment complex logic
- Ensure responsive design principles are maintained
- Test across multiple browsers and devices

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👨‍💻 Author

**Your Name**
- GitHub: [@yourusername](https://github.com/yourusername)
- LinkedIn: [Your Profile](https://linkedin.com/in/yourprofile)
- Email: your.email@example.com

## 🙏 Acknowledgments

- Bootstrap team for the excellent framework
- The open-source community for inspiration and resources
- Hostinger for reliable hosting services

## 📞 Contact & Support

For questions, issues, or suggestions:
- Open an issue on GitHub
- Email: support@icelandbeachresort.com
- Visit: [Iceland Beach Resort](https://yourwebsite.com)

---

<div align="center">

**⭐ Star this repository if you find it helpful!**

Made with ❤️ in Lagos, Nigeria

</div>