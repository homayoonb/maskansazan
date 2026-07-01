<?php
// admin_functions.php

if (!function_exists('generatePagination')) {
    function generatePagination($totalItems, $itemsPerPage, $currentPage, $url) {
        $totalPages = ceil($totalItems / $itemsPerPage);
        if ($totalPages <= 1) return '';
        
        $pagination = '<nav><ul class="pagination justify-content-center">';
        
        // دکمه قبلی
        if ($currentPage > 1) {
            $prevUrl = str_replace('page=' . $currentPage, 'page=' . ($currentPage - 1), $url);
            $pagination .= '<li class="page-item"><a class="page-link" href="' . $prevUrl . '">&laquo; قبلی</a></li>';
        }
        
        // صفحات
        $startPage = max(1, $currentPage - 2);
        $endPage = min($totalPages, $currentPage + 2);
        
        for ($i = $startPage; $i <= $endPage; $i++) {
            $active = ($i == $currentPage) ? ' active' : '';
            $pageUrl = strpos($url, 'page=') !== false 
                ? str_replace('page=' . $currentPage, 'page=' . $i, $url)
                : $url . (strpos($url, '?') !== false ? '&' : '?') . 'page=' . $i;
            
            $pagination .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $pageUrl . '">' . $i . '</a></li>';
        }
        
        // دکمه بعدی
        if ($currentPage < $totalPages) {
            $nextUrl = str_replace('page=' . $currentPage, 'page=' . ($currentPage + 1), $url);
            $pagination .= '<li class="page-item"><a class="page-link" href="' . $nextUrl . '">بعدی &raquo;</a></li>';
        }
        
        $pagination .= '</ul></nav>';
        return $pagination;
    }
}

if (!function_exists('generateSortableHeader')) {
    function generateSortableHeader($title, $field, $currentSort, $currentOrder, $baseUrl) {
        $newOrder = ($currentSort == $field && $currentOrder == 'ASC') ? 'DESC' : 'ASC';
        $active = ($currentSort == $field) ? ' active' : '';
        $icon = '';
        
        if ($currentSort == $field) {
            $icon = ($currentOrder == 'ASC') ? ' <i class="bi bi-sort-up"></i>' : ' <i class="bi bi-sort-down"></i>';
        }
        
        $url = $baseUrl;
        $url .= (strpos($url, '?') !== false ? '&' : '?') . 'sort=' . $field . '&order=' . $newOrder;
        
        return '<a href="' . $url . '" class="text-decoration-none' . $active . '">' . $title . $icon . '</a>';
    }
}
?>