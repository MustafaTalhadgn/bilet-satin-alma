document.addEventListener("DOMContentLoaded", function() {
  const toggleIcon = document.querySelector(".toggle-password");
  const passwordInput = document.getElementById("passwordInput");

  if (toggleIcon && passwordInput) {
    toggleIcon.addEventListener("click", function() {
      const currentType = passwordInput.getAttribute("type");
      if (currentType === "password") {
        passwordInput.setAttribute("type", "text");
        toggleIcon.classList.remove("bi-eye-slash");
        toggleIcon.classList.add("bi-eye");
      } else {
        passwordInput.setAttribute("type", "password");
        toggleIcon.classList.remove("bi-eye");
        toggleIcon.classList.add("bi-eye-slash");
      }
    });
  }

  const loginTop = document.querySelector(".login-top");
  const registerTop = document.querySelector(".register-top");
  const showlogin = document.querySelector(".showlogin");
  const showregister = document.querySelector(".showregister");
 if (loginTop && registerTop && showlogin && showregister) {
    loginTop.addEventListener("click", function() {
      registerTop.classList.remove("active");
      loginTop.classList.add("active");
      showloginForm();
    });

    registerTop.addEventListener("click", function() {
      loginTop.classList.remove("active");
      registerTop.classList.add("active");
      showRegisterForm();
    });
  }
  function showloginForm() {
    showlogin.classList.remove("passive");
    showregister.classList.add("passive");
    showlogin.style.zIndex = "2";
    showregister.style.zIndex = "1";
  }
  function showRegisterForm() {
    showregister.classList.remove("passive");
    showlogin.classList.add("passive");
    showregister.style.zIndex = "2";
    showlogin.style.zIndex = "1";
  }


});
