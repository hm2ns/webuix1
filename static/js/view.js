function ShowMessage(title, text, time = "Now") {
    msbox = document.getElementById("messageboxbox");
    newdiv = document.createElement('div');
    newdiv.innerHTML = `
    <div class="toast fade show" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
    <strong class="me-auto">`+ title + `</strong>
    <small class="text-muted">`+ time + `</small>
    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
</div>
<div class="toast-body">
    `+ text + `
</div>
</div>
    `;
    msbox.appendChild(newdiv);
}

function addMessage(title, text, icon = "bell", url = "") {
    document.getElementById("messageDrop").innerHTML += `
        <a class="dropdown-item" href="${url}">
			<div class="item-thumbnail">
            <div class="item-icon bg-success">
				<i class="bi bi-${icon} mx-0"></i>
			</div>
			</div>
			<div class="item-content flex-grow">
				<h6 class="ellipsis font-weight-normal">
					${title}
				</h6>
				<p class="font-weight-light small-text text-muted mb-0">
					${text}
				</p>
			</div>
		</a>\n`;
}

function activeMB() {
    document.getElementById("messageDropdown").innerHTML = `<i class="mdi mdi-bell mx-0"></i><span class="count"></span>`;
}

function deactiveMB() {
    document.getElementById("messageDropdown").innerHTML = `<i class="mdi mdi-bell mx-0"></i>`;
}

$.extend(true, $.fn.dataTable.defaults, {
    "aLengthMenu": [
        [5, 10, 15, 50, -1],
        [5, 10, 15, 50, "全部"]
    ],
    "iDisplayLength": 10,
    "language": {
        search: "",
        "sProcessing": "处理中...",
        "sLengthMenu": "显示 _MENU_ 项结果",
        "sZeroRecords": "没有匹配结果",
        "sInfo": "显示第 _START_ 至 _END_ 项结果，共 _TOTAL_ 项",
        "sInfoEmpty": "显示第 0 至 0 项结果，共 0 项",
        "sInfoFiltered": "(由 _MAX_ 项结果过滤)",
        "sInfoPostFix": "",
        "sSearch": "搜索:",
        "sUrl": "",
        "sEmptyTable": "表中数据为空",
        "sLoadingRecords": "载入中...",
        "sInfoThousands": ",",
        "oPaginate": {
            "sFirst": "首页",
            "sPrevious": "上页",
            "sNext": "下页",
            "sLast": "末页"
        },
        "oAria": {
            "sSortAscending": ": 以升序排列此列",
            "sSortDescending": ": 以降序排列此列"
        }
    }
})

function useJQDatatable(id, info = true, paging = true, search = true) {
    $('#' + id).DataTable({
        searching: search, paging: paging, info: info
    });
}

function sanitize(html) {
    const config = {
        ALLOWED_TAGS: ['b', 'i', 'a', 'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'table', 'tr', 'td', 'th', 'tbody', 'thead', 'tfoot', 'ul', 'ol', 'li', 'img', 'br', 'span', 'div', 'pre', 'code', 'blockquote', 'hr', 'strong', 'em', 'u', 's', 'strike', 'del', 'ins', 'mark', 'small', 'big', 'sub', 'sup'],  // 只允许这些标签
        ALLOWED_ATTR: ['href', 'title', 'src', 'alt'], // 只允许这些属性
        FORBID_TAGS: ['script', 'style', 'link'], // 禁止 script 和 style 标签
        FORBID_ATTR: ['onclick', 'onerror', 'onload', 'onmouseover', 'onmouseout', 'onmousemove'], // 禁止事件属性
        ALLOWED_URI_REGEXP: /^(https?|mailto):/i, // 只允许 http/https/ftp/mailto
        FORBID_URI_REGEXP: /^(javascript|vbscript):/i,
    };
    return DOMPurify.sanitize(html, config);
}

function exportTable(tableid) {
    // 获取用户输入
    const filename = document.getElementById('filename-' + tableid).value || '表格导出';
    const fileType = document.getElementById('filetype-' + tableid).value || 'xlsx';

    // 获取表格元素
    const table = document.getElementById(tableid);

    try {
        // 创建工作簿
        const wb = XLSX.utils.book_new();

        // 将表格转换为工作表
        const ws = XLSX.utils.table_to_sheet(table);

        // 将工作表添加到工作簿
        XLSX.utils.book_append_sheet(wb, ws, "导出结果");

        // 生成文件并下载
        if (fileType === 'csv') {
            // 导出为CSV
            const csv = XLSX.utils.sheet_to_csv(ws);
            const blob = new Blob([csv], {
                type: 'text/csv;charset=utf-8;'
            });
            const link = document.createElement("a");
            const url = URL.createObjectURL(blob);

            link.setAttribute("href", url);
            link.setAttribute("download", `${filename}.csv`);
            link.style.visibility = 'hidden';

            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        } else {
            // 导出为XLSX
            XLSX.writeFile(wb, `${filename}.xlsx`);
        }
    } catch (error) {
        // 错误处理
        ShowMessage('导出失败', error.message);
        console.error('导出错误:', error);
    }
}

function mardownParser(domid) {
    const dom = document.getElementById(domid);
    if (!dom) {
        console.error(`元素ID "${domid}" 不存在。`);
        return;
    }
    let text = dom.innerHTML;
    dom.innerHTML = sanitize(marked.parse(text));
    //判断dom是否有类markdown-body
    if (!dom.classList.contains("markdown-body")) {
        dom.classList.add("markdown-body");
    }
    return dom;
}

alertCounter = 0;
function showAlert(text, type = "primary", time = 5000) {
    const id = "alert-" + Date.now()+"-" + alertCounter++;
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.role = 'alert';
    alertDiv.id = id;
    
    const button = document.createElement('button');
    button.type = "button";
    button.className = "btn-close";
    button.setAttribute("data-bs-dismiss", "alert");
    button.setAttribute("aria-label", "Close");
    alertDiv.innerText = text;
    alertDiv.appendChild(button);

    document.getElementById("alertboxbox").appendChild(alertDiv);

    setTimeout(() => {
        const alertElement = document.getElementById(id);
        if (alertElement) {
            alertElement.classList.remove('show');
            alertElement.classList.add('hide');
            setTimeout(() => {
                if (alertElement.parentNode) {
                    alertElement.parentNode.removeChild(alertElement);
                }
            }, 500); // 等待动画完成后移除元素
        }
    }, time);

    return id;
}