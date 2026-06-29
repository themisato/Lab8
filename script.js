// Мобильное меню
const menuToggle = document.getElementById("menuToggle");
const mainNav = document.getElementById("mainNav");

if (menuToggle && mainNav) {
  menuToggle.addEventListener("click", () => {
    mainNav.classList.toggle("active");
    menuToggle.innerHTML = mainNav.classList.contains("active")
      ? '<i class="fas fa-times"></i>'
      : '<i class="fas fa-bars"></i>';
  });
}

// Закрытие меню при клике на пункт (на мобильных)
const navLinks = document.querySelectorAll(".main-nav a");
navLinks.forEach((link) => {
  link.addEventListener("click", () => {
    if (window.innerWidth <= 768) {
      mainNav.classList.remove("active");
      menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
    }
  });
});

// Работа выпадающего меню на мобильных
const dropdowns = document.querySelectorAll(".dropdown > a");
dropdowns.forEach((dropdown) => {
  dropdown.addEventListener("click", (e) => {
    if (window.innerWidth <= 768) {
      e.preventDefault();
      const parent = dropdown.parentElement;
      parent.classList.toggle("active");
    }
  });
});

// Плавная прокрутка для всех якорных ссылок
document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
  anchor.addEventListener("click", function (e) {
    const href = this.getAttribute("href");
    if (href.startsWith("#") && href.length > 1) {
      e.preventDefault();
      const targetElement = document.querySelector(href);
      if (targetElement) {
        window.scrollTo({
          top: targetElement.offsetTop - 80,
          behavior: "smooth",
        });
      }
    }
  });
});

// ===== ОТПРАВКА ФОРМЫ ЧЕРЕЗ FETCH =====
document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("feedbackForm");

  if (!form) return;

  form.addEventListener("submit", function (e) {
    e.preventDefault();

    const formData = new FormData(form);
    const data = {};

    for (let [key, value] of formData.entries()) {
      if (key === "languages[]") {
        if (!data.languages) data.languages = [];
        data.languages.push(value);
      } else if (key === "contract_accepted" || key === "privacyPolicy") {
        data[key] = value === "on" ? 1 : 0;
      } else {
        data[key] = value;
      }
    }

    if (data.languages && data.languages.length === 0) {
      delete data.languages;
    }

    const submitBtn = form.querySelector(".submit-btn");
    const originalText = submitBtn ? submitBtn.textContent : "Отправить";
    if (submitBtn) {
      submitBtn.textContent = "⏳ Отправка...";
      submitBtn.disabled = true;
    }

    fetch("api.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(data),
      credentials: "same-origin",
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error("Ошибка сети: " + response.status);
        }
        return response.json();
      })
      .then((result) => {
        let html = "";
        if (result.success) {
          html =
            '<div class="message success">✅ ' +
            (result.message || "Данные сохранены!") +
            "</div>";
          if (result.data && result.data.login && result.data.password) {
            html += `
                        <div class="message" style="background:rgba(255,152,0,0.15);color:#ffb74d;border:2px dashed #ff9800;padding:15px;border-radius:8px;text-align:center;">
                            <strong>🔑 Ваши данные для входа!</strong><br>
                            Логин: <strong>${result.data.login}</strong><br>
                            Пароль: <strong>${result.data.password}</strong><br>
                            <span style="color:#ff6b6b;font-size:0.8rem;">⚠️ Сохраните эти данные!</span>
                            <br><br>
                            <a href="login.php" style="display:inline-block;padding:8px 20px;background:linear-gradient(45deg,#1a5fb4,#40c9ff);color:white;border-radius:30px;text-decoration:none;font-weight:bold;">🔐 Перейти ко входу</a>
                        </div>
                    `;
          }
          form.reset();
          setTimeout(function () {
            location.reload();
          }, 3000);
        } else {
          if (result.errors) {
            let errorText = "";
            for (let key in result.errors) {
              errorText += "• " + result.errors[key] + "<br>";
            }
            html = '<div class="message error">❌ ' + errorText + "</div>";
          } else {
            html =
              '<div class="message error">❌ ' +
              (result.error || "Ошибка") +
              "</div>";
          }
        }
        document.getElementById("messageContainer").innerHTML = html;
      })
      .catch((error) => {
        document.getElementById("messageContainer").innerHTML = `
                <div class="message error">❌ Ошибка соединения с api.php<br><small>Проверьте, что файл существует</small></div>
            `;
      })
      .finally(() => {
        if (submitBtn) {
          submitBtn.textContent = originalText;
          submitBtn.disabled = false;
        }
      });
  });
});
