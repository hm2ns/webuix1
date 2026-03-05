<nav class="navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
	<div class="navbar-brand-wrapper d-flex justify-content-center">
		<div class="navbar-brand-inner-wrapper d-flex justify-content-between align-items-center w-100">
			<a class="navbar-brand brand-logo" href="/"><img style="width: calc(257px - 80px);" src="<?= GLOBAL_CONFIG::get("logo_long") ?>" alt="logo" /></a>
			<a class="navbar-brand brand-logo-mini" href="/"><img src="<?= GLOBAL_CONFIG::get("logo") ?>" alt="logo" /></a>
			<button class="navbar-toggler navbar-toggler align-self-center" type="button" data-toggle="minimize">
				<span class="bi bi-filter-left"></span>
			</button>
		</div>
	</div>
	<div class="navbar-menu-wrapper d-flex align-items-center justify-content-end">
		<ul class="navbar-nav mr-lg-4 w-100">
			<li class="nav-item nav-item d-none d-lg-block w-100" style="color:var(--frontcolor);font-size: 16px;cursor: pointer;">
				<span><?= UI_STRUCTURE::$pagename ?? "首页"  ?></span>
			</li>
		</ul>
		<ul class="navbar-nav navbar-nav-right">
			<!-- 消息 Dropdown -->
			<li class="nav-item dropdown me-1">
				<a class="nav-link count-indicator dropdown-toggle d-flex justify-content-center align-items-center" id="messageDropdown" href="#" data-bs-toggle="dropdown">
					<i class="bi bi-bell-fill mx-0"></i>
					<?= UI_NOTICE::$activeMB ? '<span class="count"></span>' : "" ?>
				</a>
				<div class="dropdown-menu dropdown-menu-right navbar-dropdown" id="messageDrop" aria-labelledby="messageDropdown">
					<p class="mb-0 font-weight-normal float-left dropdown-header">消息通知</p>
					<?php
					foreach (UI_NOTICE::$prependNotices as $message) {

					?>
						<a class="dropdown-item" href="<?php echo htmlspecialchars($message['url']); ?>">
							<div class="item-thumbnail">
								<div class="item-icon bg-success">
									<i class="bi bi-<?php echo $message['icon'] ?: "bell-fill"; ?> mx-0"></i>
								</div>
							</div>
							<div class="item-content flex-grow">
								<h6 class="ellipsis font-weight-normal">
									<?php echo $message['title']; ?>
								</h6>
								<p class="font-weight-light small-text text-muted mb-0">
									<?php echo $message['content']; ?>
								</p>
							</div>
						</a>
					<?php } ?>
				</div>
			</li>

			<!-- User Dropdown -->
			<?php if (MyAcc->isLoggedIn()): ?>
				<li class="nav-item nav-profile dropdown">
					<a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" id="profileDropdown">
						<img src="<?= user::getAvatar() ?>" alt="profile" />
						<span class="nav-profile-name"><?= user::getNickname() ?></span>
					</a>
					<div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="profileDropdown">
						<a class="dropdown-item" href="/user/profile">
							<i class="bi bi-person-lines-fill text-primary"></i>
							个人主页
						</a>
						<a class="dropdown-item" href="/user/logout">
							<i class="bi bi-box-arrow-right text-primary"></i>
							退出登录
						</a>
					</div>
				</li>
			<?php else: ?>
				<li class="nav-item nav-profile dropdown">
					<a class="nav-link" href="/user/login">
						<?= UI_ICON::bi("account-convert") ?>
						<span class="nav-profile-name">登录</span>
					</a>
				</li>
			<?php endif; ?>
		</ul>
		<button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-toggle="offcanvas">
			<span class="bi bi-list"></span>
		</button>
	</div>
</nav>
<div class="container-fluid page-body-wrapper">
	<nav class="sidebar sidebar-offcanvas" id="sidebar">
		<ul class="nav">
			<li class="nav-item">
				<a class="nav-link" href="/">
					<i class="bi bi-house menu-icon"></i>
					<span class="menu-title">主页</span>
				</a>
			</li>
			<?php
			if (
				MyAcc->hasPermission(PEM_AUTHMANAGE) ||
				MyAcc->hasPermission(PEM_USERMANAGE) ||
				MyAcc->hasPermission(PEM_DEPARTMENTMANAGE)
			):
			?>
				<!-- Dropdown of manager -->
				<li class="nav-item dropdown">
					<a class="nav-link" data-bs-toggle="collapse" href="#manager" aria-expanded="false" aria-controls="manager">
						<i class="bi bi-pie-chart menu-icon"></i>
						<span class="menu-title">系统管理</span>
						<i class="menu-arrow"></i>
					</a>
					<div class="collapse" id="manager">
						<ul class="nav flex-column sub-menu">
							<?php
							if (MyAcc->hasPermission(PEM_AUTHMANAGE)):
							?>
								<li class="nav-item"> <a class="nav-link" href="/manage/auth/list">权限组</a></li>
							<?php
							endif;
							if (MyAcc->hasPermission(PEM_USERMANAGE)):
							?>
								<li class="nav-item"> <a class="nav-link" href="/manage/user/list">用户</a></li>
							<?php
							endif;
							if (MyAcc->hasPermission(PEM_DEPARTMENTMANAGE)):
							?>
								<li class="nav-item"> <a class="nav-link" href="/manage/department/list">部门</a></li>
							<?php
							endif;
							?>
						</ul>
					</div>
				</li>
			<?php
			endif;
			?>
		</ul>
	</nav>