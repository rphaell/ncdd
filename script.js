// ==== NO CONTROLE DO DIREITO - WEBSITE INTERACTIVITY ====
// Main JavaScript file for website functionality

// ==== MOBILE MENU TOGGLE ====
const mobileToggle = document.getElementById('mobileToggle');
const navMenu = document.getElementById('navMenu');

mobileToggle.addEventListener('click', () => {
    navMenu.classList.toggle('active');
    const icon = mobileToggle.querySelector('i');
    icon.classList.toggle('fa-bars');
    icon.classList.toggle('fa-times');
});

// Close mobile menu when clicking on a link
document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', () => {
        navMenu.classList.remove('active');
        const icon = mobileToggle.querySelector('i');
        icon.classList.remove('fa-times');
        icon.classList.add('fa-bars');
    });
});

// ==== SCROLL ANIMATION ====
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -100px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
        }
    });
}, observerOptions);

document.querySelectorAll('section').forEach(section => {
    observer.observe(section);
});

// ==== ACTIVE NAVIGATION ====
const sections = document.querySelectorAll('section');
const navLinks = document.querySelectorAll('.nav-link');

window.addEventListener('scroll', () => {
    let current = '';
    
    sections.forEach(section => {
        const sectionTop = section.offsetTop;
        const sectionHeight = section.clientHeight;
        if (pageYOffset >= sectionTop - 100) {
            current = section.getAttribute('id');
        }
    });
    
    navLinks.forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href').substring(1) === current) {
            link.classList.add('active');
        }
    });
    
    // Navbar scroll effect
    const navbar = document.getElementById('navbar');
    if (window.scrollY > 50) {
        navbar.classList.add('scrolled');
    } else {
        navbar.classList.remove('scrolled');
    }
});

// ==== CONTACT FORM ====
const contactForm = document.getElementById('contactForm');
const formMessage = document.getElementById('formMessage');
const submitButton = contactForm.querySelector('.form-submit');

contactForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    // Get form values
    const name = document.getElementById('name').value.trim();
    const email = document.getElementById('email').value.trim();
    const message = document.getElementById('message').value.trim();
    
    // Validate
    if (!name || !email || !message) {
        showMessage('Por favor, preencha todos os campos obrigatórios.', 'error');
        return;
    }
    
    // Name validation
    if (name.length < 3) {
        showMessage('Nome deve ter pelo menos 3 caracteres.', 'error');
        return;
    }
    
    // Email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        showMessage('Por favor, insira um email válido.', 'error');
        return;
    }
    
    // Message validation
    if (message.length < 10) {
        showMessage('Mensagem deve ter pelo menos 10 caracteres.', 'error');
        return;
    }
    
    if (message.length > 5000) {
        showMessage('Mensagem muito longa (máximo 5000 caracteres).', 'error');
        return;
    }
    
    // Disable submit button and show loading state
    submitButton.disabled = true;
    const originalButtonText = submitButton.innerHTML;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
    showMessage('Enviando sua mensagem...', 'info');
    
    try {
        // Prepare form data
        const formData = {
            name: name,
            email: email,
            message: message
        };
        
        // Send AJAX request
        const response = await fetch('send_email.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        });
        
        // Parse JSON response
        const data = await response.json();
        
        // Handle response
        if (data.success) {
            showMessage(data.message, 'success');
            contactForm.reset();
            
            // Optional: scroll to the success message
            formMessage.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        } else {
            showMessage(data.message || 'Erro ao enviar mensagem. Tente novamente.', 'error');
            
            // Log debug info if available
            if (data.debug) {
                console.error('Debug info:', data.debug);
            }
        }
        
    } catch (error) {
        console.error('Erro ao enviar email:', error);
        showMessage('Erro de conexão. Verifique sua internet e tente novamente.', 'error');
    } finally {
        // Re-enable submit button
        submitButton.disabled = false;
        submitButton.innerHTML = originalButtonText;
    }
});

function showMessage(message, type) {
    formMessage.textContent = message;
    formMessage.className = `form-message ${type}`;
    formMessage.style.display = 'block';
    
    // Auto-hide after 8 seconds (except for info messages)
    if (type !== 'info') {
        setTimeout(() => {
            formMessage.style.display = 'none';
        }, 8000);
    }
}

// ==== SMOOTH SCROLL ====
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            const offsetTop = target.offsetTop - 80;
            window.scrollTo({
                top: offsetTop,
                behavior: 'smooth'
            });
        }
    });
});

// ==== INITIAL VISIBILITY ====
// Make hero section visible immediately
document.querySelector('.hero').classList.add('visible');
