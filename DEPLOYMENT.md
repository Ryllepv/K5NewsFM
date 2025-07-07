# K5 News FM Website Deployment Guide

## Pre-Deployment Checklist

### Content Updates Required
- [ ] Replace placeholder images with actual station photos
- [ ] Update contact information (phone, email, address)
- [ ] Add real streaming URL in audio player
- [ ] Update show schedule with actual programming
- [ ] Replace host photos and bios with real information
- [ ] Update social media links
- [ ] Add actual sponsor logos and information
- [ ] Update news and announcements with current content

### Technical Configuration
- [ ] Set up actual audio streaming URL
- [ ] Configure contact form backend (PHP/Node.js/etc.)
- [ ] Set up analytics (Google Analytics, etc.)
- [ ] Configure SEO meta tags with actual domain
- [ ] Test all forms and interactive elements
- [ ] Optimize images for web (WebP format recommended)

## Deployment Options

### Option 1: Static Web Hosting
**Recommended for**: Simple deployment, cost-effective

**Providers**: Netlify, Vercel, GitHub Pages, AWS S3

**Steps**:
1. Upload all files to hosting provider
2. Configure custom domain
3. Set up SSL certificate (usually automatic)
4. Configure redirects if needed

### Option 2: Traditional Web Hosting
**Recommended for**: Full control, custom backend integration

**Requirements**: cPanel, FTP access, or file manager

**Steps**:
1. Upload files via FTP or file manager
2. Configure domain DNS settings
3. Set up SSL certificate
4. Configure .htaccess for redirects (Apache)

### Option 3: Content Delivery Network (CDN)
**Recommended for**: Global audience, fast loading

**Providers**: Cloudflare, AWS CloudFront, Azure CDN

**Benefits**:
- Faster loading times worldwide
- DDoS protection
- Automatic optimization

## Post-Deployment Configuration

### 1. Audio Streaming Setup
```javascript
// In js/main.js, update the streaming URL:
radioStream.src = 'https://your-stream-url.com/live';
```

### 2. Contact Form Backend
Create a backend endpoint to handle form submissions:

**PHP Example** (contact-handler.php):
```php
<?php
if ($_POST) {
    $name = $_POST['firstName'] . ' ' . $_POST['lastName'];
    $email = $_POST['email'];
    $subject = $_POST['subject'];
    $message = $_POST['message'];
    
    // Send email or save to database
    mail('info@k5newsfm.com', $subject, $message, "From: $email");
    
    echo json_encode(['success' => true]);
}
?>
```

### 3. Analytics Setup
Add Google Analytics or similar:
```html
<!-- Add before closing </head> tag -->
<script async src="https://www.googletagmanager.com/gtag/js?id=GA_MEASUREMENT_ID"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'GA_MEASUREMENT_ID');
</script>
```

### 4. SEO Configuration
Update meta tags in index.html:
```html
<meta property="og:url" content="https://your-actual-domain.com">
<meta property="og:image" content="https://your-actual-domain.com/images/og-image.jpg">
```

## Performance Optimization

### Image Optimization
1. Convert images to WebP format
2. Use appropriate sizes (max 1920px width for hero images)
3. Compress images (TinyPNG, ImageOptim)

### Caching Configuration
Add to .htaccess (Apache) or nginx.conf:
```apache
# Apache .htaccess
<IfModule mod_expires.c>
    ExpiresActive on
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
</IfModule>
```

### Compression
Enable Gzip compression:
```apache
# Apache .htaccess
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>
```

## Security Considerations

### HTTPS Setup
- Ensure SSL certificate is properly configured
- Redirect HTTP to HTTPS
- Update all internal links to use HTTPS

### Content Security Policy
Add CSP header:
```html
<meta http-equiv="Content-Security-Policy" content="default-src 'self'; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; script-src 'self' https://cdn.jsdelivr.net; img-src 'self' data: https:; font-src 'self' https://cdn.jsdelivr.net;">
```

## Monitoring and Maintenance

### Regular Updates
- Update Bootstrap and other dependencies quarterly
- Review and update content monthly
- Check for broken links and images
- Monitor site performance

### Backup Strategy
- Set up automated backups
- Test restore procedures
- Keep local copies of all files

### Analytics Monitoring
- Track page views and user engagement
- Monitor audio player usage
- Analyze contact form submissions
- Review mobile vs desktop usage

## Troubleshooting

### Common Issues
1. **Audio player not working**: Check streaming URL and CORS settings
2. **Images not loading**: Verify file paths and permissions
3. **Contact form not submitting**: Check backend configuration
4. **Mobile layout issues**: Test responsive design on various devices

### Browser Testing
Test on:
- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Support

For technical issues or questions about deployment, refer to:
- Web hosting provider documentation
- Bootstrap 5 documentation
- Browser developer tools for debugging

---

**Note**: This is a frontend-only website. For dynamic features like live chat, real-time updates, or user accounts, additional backend development will be required.
