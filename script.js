document.addEventListener('DOMContentLoaded', function() 
{
    // Select the Sign Up button and link
    const signUpBtn = document.querySelector('.sign_up .btn');
    const signUpLink = document.querySelector('.sign_up .Sign-Up');

    // Change button and link colors
    if (signUpBtn) 
    {
        signUpBtn.style.backgroundColor = '#17a2b8'; 
        signUpBtn.style.color = '#fff';
        signUpBtn.style.border = 'none';
        signUpBtn.style.transition = 'background 0.3s';

        // Hover effect
        signUpBtn.addEventListener('mouseover', function() 
        {
            signUpBtn.style.backgroundColor = '#138496';
        });
        signUpBtn.addEventListener('mouseout', function() 
        {
            signUpBtn.style.backgroundColor = '#17a2b8';
        });

        // Click event to redirect to register form
        signUpBtn.addEventListener('click', function(e) 
        {
            e.preventDefault();
            window.location.href = '/auth/Register.php';
        });
    }

    // handle the link inside the button
    if (signUpLink) 
        {
        signUpLink.style.color = '#fff';
        signUpLink.addEventListener('click', function(e) 
        {
            e.preventDefault();
            window.location.href = '/auth/Register.php';
        });
    }

   function showCategories(category) {
    document.querySelectorAll('.menu_box > div[data-category]').forEach(group => {
        if (category === 'all' || group.getAttribute('data-category') === category) {
            group.style.display = 'flex';
        } else {
            group.style.display = 'none';
        }
    });
}

// Set up carousel click events
document.querySelectorAll('.carousel-item').forEach(item => {
    item.addEventListener('click', function() {
        // Remove 'active' from all
        document.querySelectorAll('.carousel-item').forEach(i => i.classList.remove('active'));

        // Add 'active' to clicked item
        this.classList.add('active');

        // Get category from data attribute
        const category = this.getAttribute('data-category');

        // Show matching categories
        showCategories(category);
    });
});

// Show all categories by default on page load
showCategories('all');
});


// Redirect to Home page when logo is clicked
document.addEventListener('DOMContentLoaded', function() {
    var navLogo = document.getElementById('navLogo');
    if (navLogo) {
        navLogo.addEventListener('click', function() {
            window.location.href = '/Home.php#Home';
        });
    }
});

// scroll horizontally through carousel items using buttons
document.addEventListener('DOMContentLoaded', function () {
    const track = document.querySelector('.carousel-track');
    const leftBtn = document.querySelector('.carousel-btn.left');
    const rightBtn = document.querySelector('.carousel-btn.right');
    
    leftBtn.addEventListener('click', () => {
        track.scrollBy({
            left: -200,
            behavior: 'smooth'
        });
    });

    rightBtn.addEventListener('click', () => {
        track.scrollBy({
            left: 200,
            behavior: 'smooth'
        });
    });
});



