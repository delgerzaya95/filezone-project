/**
 * Filezone Admin Panel - Tailwind Configuration
 * Энэ файл нь админ панелийн өнгө, фонт болон бусад тохиргоог удирдана.
 */

tailwind.config = {
    theme: {
        extend: {
            fontFamily: {
                // Үндсэн фонт: Inter
                sans: ['Inter', 'sans-serif'],
            },
            colors: {
                // Filezone Brand Colors
                primary: '#4F46E5', // Indigo-600 (Үндсэн товчлуур, идэвхтэй элемент)
                secondary: '#6366F1', // Indigo-500
                
                // Dark Mode / Sidebar Colors
                dark: '#1E293B',    // Slate-800
                sidebar: '#0F172A', // Slate-900 (Сайдбарны дэвсгэр)
                
                // Status Colors (Custom shades if needed)
                success: '#10B981', // Emerald-500
                warning: '#F59E0B', // Amber-500
                danger: '#EF4444',  // Red-500
            },
            boxShadow: {
                'soft': '0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03)',
            }
        }
    }
}