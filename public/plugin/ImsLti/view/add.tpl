<div>
    <div class="mx-auto w-full max-w-6xl px-4 py-8 lg:px-6">
        <a
            href="{{ back_url }}"
            class="mb-6 inline-flex items-center gap-2 rounded-xl border border-gray-25 bg-white px-4 py-2 text-body-2 font-semibold text-gray-90 shadow-sm transition hover:border-primary hover:text-primary"
        >
            <i class="mdi mdi-arrow-left" aria-hidden="true"></i>
            <span>{{ back_label }}</span>
        </a>

        <div class="overflow-hidden rounded-2xl border border-gray-25 bg-white shadow-xl">
            <div class="border-b border-gray-25 bg-support-2 px-6 py-5 lg:px-8">
                <h1 class="text-2xl font-semibold text-gray-90">{{ page_title }}</h1>
                <p class="mt-2 max-w-3xl text-body-2 text-gray-50">
                    {{ page_description }}
                </p>
            </div>

            <div class="p-6 lg:p-8">
                <div id="ims-lti-create-form" class="w-full">
                    {{ form|raw }}
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const root = document.getElementById('ims-lti-create-form');

    if (!root) {
        return;
    }

    const removeGridClasses = (element) => {
        if (!element || !element.classList) {
            return;
        }

        [...element.classList].forEach((className) => {
            if (
                className.startsWith('col-') ||
                className.startsWith('col_') ||
                className.startsWith('span')
            ) {
                element.classList.remove(className);
            }
        });
    };

    const addClasses = (elements, classes) => {
        elements.forEach((element) => {
            if (!element || !element.classList) {
                return;
            }

            classes.forEach((className) => element.classList.add(className));
        });
    };

    const form = root.querySelector('form');
    if (form) {
        form.classList.add('w-full', 'space-y-6');
        form.removeAttribute('style');
    }

    root.querySelectorAll('.row, .form-group, .field, .p-field').forEach((element) => {
        removeGridClasses(element);
        element.classList.add('mb-6', 'w-full', 'max-w-none');
        element.removeAttribute('style');
    });

    root.querySelectorAll('.col-sm-2, .col-sm-3, .col-sm-4, .col-sm-6, .col-sm-8, .col-sm-9, .col-sm-10, .col-sm-12, .col-md-2, .col-md-3, .col-md-4, .col-md-6, .col-md-8, .col-md-9, .col-md-10, .col-md-12').forEach((element) => {
        removeGridClasses(element);
        element.classList.add('w-full', 'max-w-none', 'px-0');
        element.removeAttribute('style');
    });

    root.querySelectorAll('label.control-label, .control-label').forEach((label) => {
        label.classList.add('mb-2', 'block', 'w-full', 'text-body-2', 'font-semibold', 'text-gray-90');
        label.style.position = 'static';
        label.style.transform = 'none';
        label.style.background = 'transparent';
        label.style.padding = '0';
        label.style.marginBottom = '0.5rem';
    });

    const floatWrappers = [...root.querySelectorAll('.p-float-label')];

    floatWrappers.forEach((wrapper) => {
        const label = wrapper.querySelector('label');
        const controls = wrapper.querySelectorAll('input, textarea, select, .p-inputtext');

        wrapper.classList.remove('p-float-label');
        wrapper.classList.add('flex', 'w-full', 'max-w-3xl', 'flex-col', 'gap-2');
        wrapper.removeAttribute('style');

        if (label) {
            label.classList.add('block', 'w-full', 'text-body-2', 'font-semibold', 'text-gray-90');
            label.style.position = 'static';
            label.style.top = 'auto';
            label.style.left = 'auto';
            label.style.right = 'auto';
            label.style.bottom = 'auto';
            label.style.transform = 'none';
            label.style.background = 'transparent';
            label.style.padding = '0';
            label.style.margin = '0';
            label.style.zIndex = 'auto';
        }

        controls.forEach((control) => {
            control.classList.add(
                'mt-0',
                'block',
                'w-full',
                'rounded-xl',
                'border',
                'border-gray-25',
                'bg-white',
                'px-4',
                'py-3',
                'text-body-2',
                'text-gray-90',
                'shadow-sm',
                'placeholder-gray-50',
                'focus:border-primary',
                'focus:ring-2',
                'focus:ring-primary'
            );

            control.style.marginTop = '0';
        });
    });

    addClasses(
        root.querySelectorAll('input[type="text"], input[type="url"], input[type="password"], input[type="email"], textarea, select, .form-control, .p-inputtext'),
        [
            'mt-0',
            'block',
            'w-full',
            'max-w-3xl',
            'rounded-xl',
            'border',
            'border-gray-25',
            'bg-white',
            'px-4',
            'py-3',
            'text-body-2',
            'text-gray-90',
            'shadow-sm',
            'placeholder-gray-50',
            'focus:border-primary',
            'focus:ring-2',
            'focus:ring-primary'
        ]
    );

    addClasses(
        root.querySelectorAll('textarea'),
        ['min-h-[120px]']
    );

    root.querySelectorAll('.radio, .checkbox').forEach((element) => {
        element.classList.add('mb-3', 'flex', 'items-start', 'gap-3');
        element.removeAttribute('style');
    });

    addClasses(
        root.querySelectorAll('input[type="radio"], input[type="checkbox"]'),
        ['mt-1', 'h-4', 'w-4', 'rounded', 'border-gray-25', 'text-primary', 'focus:ring-primary']
    );

    addClasses(
        root.querySelectorAll('.help-block, .form-text, small'),
        ['mt-2', 'block', 'max-w-3xl', 'text-caption', 'text-gray-50']
    );

    addClasses(
        root.querySelectorAll('.alert, .alert-info'),
        ['mb-6', 'rounded-xl', 'border', 'border-support-3', 'bg-support-1', 'p-4', 'text-body-2', 'text-support-4']
    );

    addClasses(
        root.querySelectorAll('.error-message, .form-error, .text-danger'),
        ['mt-2', 'block', 'text-caption', 'text-danger']
    );

    root.querySelectorAll('hr').forEach((element) => {
        element.classList.add('my-6', 'border-gray-25');
    });
});
</script>
