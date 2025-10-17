document.addEventListener("click", function(e) {
  if (e.target.classList.contains("toggle-password-register")) {
    const passwordInput = document.getElementById("passwordInputRegister");
    if (!passwordInput) return;

    const currentType = passwordInput.getAttribute("type");
    if (currentType === "password") {
      passwordInput.setAttribute("type", "text");
      e.target.classList.remove("bi-eye-slash");
      e.target.classList.add("bi-eye");
    } else {
      passwordInput.setAttribute("type", "password");
      e.target.classList.remove("bi-eye");
      e.target.classList.add("bi-eye-slash");
    }
  }
});
