// Мобильное меню
const menuToggle = document.getElementById("menuToggle");
const mainNav = document.getElementById("mainNav");

menuToggle.addEventListener("click", () => {
  mainNav.classList.toggle("active");
  menuToggle.innerHTML = mainNav.classList.contains("active")
    ? '<i class="fas fa-times"></i>'
    : '<i class="fas fa-bars"></i>';
});

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
    // Прокручиваем только если это якорь на текущей странице (не начинается с имени файла)
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
    // Если ссылка ведет на другую страницу с якорем (например, index.html#characters-block),
    // то стандартное поведение браузера сработает само.
  });
});

// Обработка формы обратной связи
const contactForm = document.getElementById("contactForm");
const formMessage = document.getElementById("formMessage");
const submitBtn = document.getElementById("submitBtn");
const btnText = document.getElementById("btnText");
const spinner = document.getElementById("spinner");

// Скрыть поле антиспама для реальных пользователей
document.addEventListener("DOMContentLoaded", () => {
  const antispamField = document.getElementById("antispam");
  if (antispamField) {
    antispamField.style.display = "none";
  }
});

// Валидация формы на лету
function validateField(field, type) {
  const value = field.value.trim();
  const errorElement = document.getElementById(`${field.id}Error`);

  if (!errorElement) return true;

  errorElement.textContent = "";

  // Проверка в зависимости от типа поля
  switch (type) {
    case "name":
      if (value.length < 2) {
        errorElement.textContent = "Имя должно содержать минимум 2 символа";
        return false;
      }
      if (value.length > 50) {
        errorElement.textContent = "Имя не должно превышать 50 символов";
        return false;
      }
      if (!/^[a-zA-Zа-яА-ЯёЁ\s\-]+$/u.test(value)) {
        errorElement.textContent =
          "Имя может содержать только буквы, пробелы и дефисы";
        return false;
      }
      break;

    case "phone":
      if (!/^[\+]?[0-9\s\-\(\)]{10,20}$/.test(value)) {
        errorElement.textContent = "Введите корректный номер телефона";
        return false;
      }
      break;

    case "email":
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
        errorElement.textContent = "Введите корректный email адрес";
        return false;
      }
      break;

    case "comment":
      if (value.length < 10) {
        errorElement.textContent =
          "Комментарий должен содержать минимум 10 символов";
        return false;
      }
      if (value.length > 1000) {
        errorElement.textContent =
          "Комментарий не должен превышать 1000 символов";
        return false;
      }
      break;
  }

  return true;
}

// Добавляем валидацию при вводе
const formFields = [
  { id: "name", type: "name" },
  { id: "phone", type: "phone" },
  { id: "email", type: "email" },
  { id: "comment", type: "comment" },
];

formFields.forEach((field) => {
  const element = document.getElementById(field.id);
  if (element) {
    element.addEventListener("blur", () => validateField(element, field.type));
    element.addEventListener("input", () => {
      const errorElement = document.getElementById(`${field.id}Error`);
      if (errorElement) errorElement.textContent = "";
    });
  }
});

// Обработка отправки формы
if (contactForm) {
  contactForm.addEventListener("submit", async (e) => {
    e.preventDefault();

    // Сброс предыдущих сообщений
    formMessage.className = "form-message";
    formMessage.textContent = "";

    // Валидация всех полей
    let isValid = true;
    formFields.forEach((field) => {
      const element = document.getElementById(field.id);
      if (element && !validateField(element, field.type)) {
        isValid = false;
      }
    });

    if (!isValid) {
      formMessage.className = "form-message error";
      formMessage.textContent = "Пожалуйста, исправьте ошибки в форме";
      return;
    }

    // Показываем спиннер
    btnText.style.opacity = "0.5";
    spinner.classList.remove("hidden");
    submitBtn.disabled = true;

    // Собираем данные формы
    const formData = new FormData(contactForm);

    try {
      // Отправляем данные на сервер
      const response = await fetch("send_email.php", {
        method: "POST",
        body: formData,
      });

      const result = await response.json();

      // Обработка ответа
      if (result.success) {
        // Успешная отправка
        formMessage.className = "form-message success";
        formMessage.textContent = result.message;
        contactForm.reset();

        // Прокрутка к сообщению об успехе
        formMessage.scrollIntoView({ behavior: "smooth", block: "nearest" });
      } else {
        // Ошибка отправки или валидации
        formMessage.className = "form-message error";
        formMessage.textContent = result.message;

        // Показываем ошибки полей, если они есть
        if (result.errors) {
          Object.keys(result.errors).forEach((fieldName) => {
            const errorElement = document.getElementById(`${fieldName}Error`);
            if (errorElement) {
              errorElement.textContent = result.errors[fieldName];
            }
          });
        }
      }
    } catch (error) {
      // Ошибка сети или сервера
      console.error("Ошибка отправки формы:", error);
      formMessage.className = "form-message error";
      formMessage.textContent =
        "Ошибка сети. Пожалуйста, проверьте подключение к интернету.";
    } finally {
      // Скрываем спиннер
      btnText.style.opacity = "1";
      spinner.classList.add("hidden");
      submitBtn.disabled = false;
    }
  });
}

// Защита от спама: заполняем скрытое поле для ботов
if (document.getElementById("antispam")) {
  // Боты часто заполняют все поля, включая скрытые
  // Оставляем поле пустым для настоящих пользователей
  document.getElementById("antispam").value = "";
}
// ===== ОТПРАВКА ФОРМЫ ЧЕРЕЗ FETCH (AJAX) =====
// Этот код обрабатывает отправку формы без перезагрузки страницы
// Если JavaScript выключен, форма отправится обычным способом

document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("feedbackForm");

  // Если форма не найдена - выходим
  if (!form) return;

  // Сохраняем оригинальный action и method
  const originalAction = form.getAttribute("action") || "";
  const originalMethod = form.getAttribute("method") || "GET";

  // Перехватываем отправку формы
  form.addEventListener("submit", function (e) {
    // Проверяем, не отправилась ли форма уже через обычный способ
    if (form.dataset.submitted === "true") return;

    e.preventDefault();

    // Проверяем, включен ли JavaScript (всегда true здесь)
    // Но мы можем отправить AJAX-запрос

    // Собираем данные формы
    const formData = new FormData(form);
    const data = {};

    for (let [key, value] of formData.entries()) {
      // Обрабатываем чекбоксы
      if (key === "privacyPolicy" || key === "contract_accepted") {
        data[key] = value === "on" ? 1 : 0;
      } else {
        data[key] = value;
      }
    }

    // Добавляем языки (если есть)
    const languagesSelect = document.getElementById("languages");
    if (languagesSelect) {
      const selected = Array.from(languagesSelect.selectedOptions).map(
        (opt) => opt.value,
      );
      if (selected.length > 0) {
        data.languages = selected;
      }
    }

    // Показываем индикатор загрузки
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn ? submitBtn.textContent : "Отправить";
    if (submitBtn) {
      submitBtn.textContent = "Отправка...";
      submitBtn.disabled = true;
    }

    // Определяем, авторизован ли пользователь
    const isAuth = document.querySelector(".user-info") !== null;
    const method = isAuth ? "PUT" : "POST";

    // Отправляем запрос к API
    fetch("/api.php", {
      method: method,
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
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
        if (result.success) {
          // Успешная отправка
          showMessage(result.message || "Данные успешно сохранены!", "success");

          // Если это новый пользователь - показываем логин и пароль
          if (result.data && result.data.login && result.data.password) {
            showCredentials(result.data.login, result.data.password);
          }

          // Если есть profile_url - обновляем страницу или перенаправляем
          if (result.data && result.data.profile_url) {
            setTimeout(() => {
              window.location.href = result.data.profile_url;
            }, 3000);
          }

          // Очищаем форму
          form.reset();

          // Обновляем данные пользователя на странице
          if (result.data && result.data.id) {
            updateUserInfo(result.data);
          }
        } else {
          // Показываем ошибки
          if (result.errors) {
            let errorText = "";
            for (let key in result.errors) {
              errorText += result.errors[key] + "\n";
            }
            showMessage("Ошибки:\n" + errorText, "error");
          } else {
            showMessage(
              result.error || "Ошибка при сохранении данных",
              "error",
            );
          }
        }
      })
      .catch((error) => {
        console.error("Ошибка:", error);
        showMessage("Ошибка соединения. Попробуйте позже.", "error");

        // Если fetch не работает - отправляем обычным способом
        // Устанавливаем флаг и отправляем форму
        form.dataset.submitted = "true";

        // Восстанавливаем оригинальные action и method
        if (originalAction) {
          form.setAttribute("action", originalAction);
        } else {
          form.removeAttribute("action");
        }
        form.setAttribute("method", originalMethod);

        // Отправляем форму обычным способом
        form.submit();
      })
      .finally(() => {
        // Восстанавливаем кнопку
        if (submitBtn) {
          submitBtn.textContent = originalText;
          submitBtn.disabled = false;
        }
      });
  });

  // Функция показа сообщений
  function showMessage(text, type) {
    const container = document.getElementById("messageContainer");
    if (!container) return;

    const msg = document.createElement("div");
    msg.className = "message " + type;
    msg.textContent = text;
    msg.style.whiteSpace = "pre-line";

    container.innerHTML = "";
    container.appendChild(msg);

    // Автоматическое скрытие через 5 секунд
    setTimeout(() => {
      if (msg.parentNode) {
        msg.remove();
      }
    }, 5000);
  }

  // Функция показа логина и пароля
  function showCredentials(login, password) {
    const container = document.getElementById("messageContainer");
    if (!container) return;

    const html = `
            <div class="message success" style="background: #fff3e0; border-color: #ff9800;">
                <strong>🔑 Ваши данные для входа:</strong><br>
                Логин: <strong>${login}</strong><br>
                Пароль: <strong>${password}</strong><br>
                <span style="color: #d32f2f; font-size: 0.8rem;">⚠️ Сохраните эти данные! Пароль отображается только один раз.</span>
            </div>
        `;

    container.innerHTML = html;
  }

  // Функция обновления информации о пользователе
  function updateUserInfo(data) {
    const userInfo = document.querySelector(".user-info");
    if (userInfo) {
      const nameSpan = userInfo.querySelector("strong");
      if (nameSpan) {
        nameSpan.textContent = data.full_name || "Пользователь";
      }
    }

    // Обновляем скрытое поле edit_id
    const editInput = document.querySelector('input[name="edit_id"]');
    if (editInput && data.id) {
      editInput.value = data.id;
    }
  }
});

// ===== ЗАГРУЗКА ДАННЫХ ПРИ АВТОРИЗАЦИИ =====
// Если пользователь авторизован, загружаем его данные
document.addEventListener("DOMContentLoaded", function () {
  const userInfo = document.querySelector(".user-info");
  if (!userInfo) return; // Не авторизован

  // Загружаем данные через API
  fetch("/api.php", {
    method: "GET",
    headers: {
      Accept: "application/json",
    },
    credentials: "same-origin",
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.success && result.data) {
        // Заполняем поля формы
        const data = result.data;
        const fields = [
          "full_name",
          "phone",
          "email",
          "birth_date",
          "gender",
          "biography",
        ];

        fields.forEach((field) => {
          const input = document.querySelector(`[name="${field}"]`);
          if (input && data[field]) {
            if (input.type === "radio") {
              const radio = document.querySelector(
                `[name="${field}"][value="${data[field]}"]`,
              );
              if (radio) radio.checked = true;
            } else {
              input.value = data[field];
            }
          }
        });

        // Заполняем языки
        if (data.languages) {
          const select = document.getElementById("languages");
          if (select) {
            Array.from(select.options).forEach((opt) => {
              opt.selected = data.languages.includes(opt.value);
            });
          }
        }

        // Чекбокс контракта
        const contractCheck = document.querySelector(
          '[name="contract_accepted"]',
        );
        if (contractCheck && data.contract_accepted) {
          contractCheck.checked = true;
        }
      }
    })
    .catch((error) => console.error("Ошибка загрузки данных:", error));
});
