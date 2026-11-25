/**
 * Initializes shortcode instances and handles paginated project requests.
 */
(function ($) {
    "use strict";

    if (typeof wppProjects === "undefined") {
        return;
    }

    /**
     * Builds project cards from JSON and appends or replaces the current list.
     */
    function renderProjects(results, projects, append) {
        if (append) {
            Array.prototype.forEach.call(
                results.querySelectorAll(".wpp-projects__error"),
                function (error) {
                    error.parentNode.removeChild(error);
                }
            );
        }

        var list = append ? results.querySelector(".wpp-projects__list") : null;

        if (!append) {
            results.replaceChildren();
        }

        if (projects.length === 0 && (!append || !list || list.children.length === 0)) {
            var emptyMessage = document.createElement("p");
            emptyMessage.className = "wpp-projects__empty";
            emptyMessage.textContent = wppProjects.noProjects;
            if (!append) {
                results.appendChild(emptyMessage);
            } else if (!list) {
                results.replaceChildren(emptyMessage);
            }
            return;
        }

        if (!list) {
            list = document.createElement("ul");
            list.className = "wpp-projects__list";
            results.replaceChildren(list);
        }

        var existingIds = {};
        Array.prototype.forEach.call(list.querySelectorAll("[data-project-id]"), function (item) {
            existingIds[item.getAttribute("data-project-id")] = true;
        });

        projects.forEach(function (project) {
            var projectId = String(project.id);
            if (append && existingIds[projectId]) {
                return;
            }

            var item = document.createElement("li");
            var heading = document.createElement("h3");
            var link = document.createElement("a");
            var details = document.createElement("dl");

            item.className = "wpp-projects__item";
            item.setAttribute("data-project-id", projectId);
            if (project.thumbnail) {
                var image = document.createElement("img");
                image.src = project.thumbnail;
                image.alt = project.title;
                image.className = "wpp-projects__image attachment-medium size-medium";
                item.appendChild(image);
            }

            link.href = project.url;
            link.textContent = project.title;
            heading.appendChild(link);
            item.appendChild(heading);

            details.className = "wpp-projects__meta";
            [
                { label: wppProjects.clientName, value: project.client_name },
                { label: wppProjects.projectStatus, value: project.project_status },
                { label: wppProjects.budget, value: project.budget }
            ].forEach(function (field) {
                var label = document.createElement("dt");
                var value = document.createElement("dd");
                label.textContent = field.label;
                value.textContent = field.value === undefined || field.value === null || field.value === ""
                    ? wppProjects.notSpecified
                    : String(field.value);
                details.appendChild(label);
                details.appendChild(value);
            });
            item.appendChild(details);
            list.appendChild(item);
            existingIds[projectId] = true;
        });

        if (!append) {
            results.appendChild(list);
        }
    }

    /**
     * Shows an error without removing existing cards during a load-more request.
     */
    function showRequestError(results, append) {
        if (!append) {
            results.textContent = wppProjects.error;
            return;
        }

        var error = document.createElement("p");
        error.className = "wpp-projects__error";
        error.textContent = wppProjects.error;
        results.appendChild(error);
    }

    /**
     * Stores the current page and result count for the load-more control.
     */
    function updatePagination(state, status, pagination) {
        state.page = pagination.current_page;
        state.status = status;
        state.totalPages = pagination.total_pages;
    }

    /**
     * Requests one page from admin-ajax.php and updates the load-more state.
     */
    function loadProjects(container, status, page, append) {
        var state = $.data(container, "wppProjectPagination");
        var results = container.querySelector(".wpp-projects__results");

        if (!append) {
            results.textContent = wppProjects.loading;
        }
        results.setAttribute("aria-busy", "true");
        var loadMore = container.querySelector(".wpp-projects__load-more");
        loadMore.disabled = true;

        $.ajax({
            url: wppProjects.ajaxUrl,
            method: "POST",
            dataType: "json",
            data: {
                action: "wpp_load_projects",
                nonce: wppProjects.nonce,
                status: status,
                page: page,
                per_page: state.perPage
            }
        })
            .done(function (response) {
                if (
                    !response.success ||
                    !response.data ||
                    !Array.isArray(response.data.projects) ||
                    !response.data.pagination ||
                    typeof response.data.pagination.current_page !== "number" ||
                    typeof response.data.pagination.total_pages !== "number"
                ) {
                    showRequestError(results, append);
                    return;
                }
                renderProjects(results, response.data.projects, append);
                updatePagination(state, status, response.data.pagination);
            })
            .fail(function () {
                showRequestError(results, append);
            })
            .always(function () {
                results.setAttribute("aria-busy", "false");
                loadMore.disabled = state.totalPages !== null &&
                    (state.totalPages === 0 || state.page >= state.totalPages);
            });
    }

    $(".wpp-projects").each(function () {
        var container = this;
        var controls = $(container);
        var state = {
            page: 1,
            perPage: wppProjects.perPage,
            totalPages: null,
            status: container.getAttribute("data-status") || "all"
        };

        $.data(container, "wppProjectPagination", state);

        controls.find(".wpp-projects__load-more").on("click", function () {
            if (state.totalPages === null) {
                loadProjects(container, state.status, 1, false);
            } else if (state.page < state.totalPages) {
                loadProjects(container, state.status, state.page + 1, true);
            }
        });
        loadProjects(container, state.status, state.page, false);
    });
})(jQuery);
