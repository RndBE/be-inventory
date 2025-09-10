<div>
    <div class="pos intro-y grid grid-cols-12 gap-5 mt-5">
        <div class="intro-y col-span-12 text-right">
            <button onclick="printDiv('print-section')"
                class="px-4 py-2 bg-theme-1 text-white rounded hover:bg-theme-1/90">
                Print / Preview PDF
            </button>
        </div>

        <div class="intro-y col-span-12">
            <div id="print-section">
                <!-- Semua konten laporan kamu di sini -->
            </div>
        </div>
    </div>
</div>

<script>
    function printDiv(divId) {
        const printContents = document.getElementById(divId).innerHTML;

        // Buka window baru untuk print
        const printWindow = window.open('', '_blank', 'width=1100,height=600');

        // Ambil semua CSS (link dan style)
        const styles = Array.from(document.querySelectorAll('link[rel="stylesheet"], style'))
            .map(node => node.outerHTML)
            .join('');

        // Tulis konten ke window baru
        printWindow.document.write(`
            <html>
                <head>
                    <title>Print Preview</title>
                    ${styles}
                    <style>
                        @media print {
                            button {
                                display: none !important;
                            }
                        }
                    </style>
                </head>
                <body>
                    ${printContents}
                </body>
            </html>
        `);

        printWindow.document.close();

        // Tunggu window baru selesai render
        printWindow.onload = function () {
            printWindow.focus();
            printWindow.print();
        };
    }
</script>
