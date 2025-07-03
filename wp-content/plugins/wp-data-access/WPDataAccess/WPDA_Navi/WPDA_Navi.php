<?php

namespace WPDataAccess\WPDA_Navi {

    use WPDataAccess\WPDA;

    class WPDA_Navi {

        private $option_legacy_tools;

        public function __construct() {
            $this->option_legacy_tools = WPDA::get_option( WPDA::OPTION_PLUGIN_LEGACY_TOOLS );
        }

		public function show() {
			?>
			<div class="wpda-navi-container">
				<?php
				$this->header();
				$this->tool_guide();
				$this->hot_topics();
				?>
			</div>
			<?php
		}

		private function header() {
            ?>
			<div class="wpda-navi-container-header">
				<div class="wpda-navi-container-header-title">
					<div>
						<h1>
							Welcome to WP Data Access
						</h1>

						<h2>
                            A powerful data-driven App Builder with an intuitive Table Builder, a highly customizable Form Builder and interactive Chart support in 35 languages
						</h2>
					</div>

					<div></div>
				</div>

				<div class="wpda-navi-container-header-image">
					<img src="<?php echo plugins_url('../../assets/images/coding-isometric-01-blauw.png', __FILE__); ?>"/>
				</div>
			</div>
			<?php
		}

        private function tool_status( $tool ) {
            ?>
            <div onclick="setLegacyToolStatus(this)" class="tool_status_icon">
                <?php
                if ( true === $tool[0] ) {
                    ?>
                    <i class="fas fa-toggle-on" style="font-size: 28px;"></i>
                    <?php
                } else {
                    ?>
                    <i class="fas fa-toggle-off" style="font-size: 28px;"></i>
                    <?php
                }
                ?>
            </div>
            <?php
        }

		private function tool_guide() {
			?>
            <div class="wpda-navi-container-content"
                 style="display: grid; grid-template-columns: auto auto; justify-content: space-between; align-items: center; padding-bottom: 0;"
            >
                <h2>
                    Tool Guide
                </h2>

                <div id="wpda-legacy-tool-settings">
                    <a href="javascript:void(0)" onclick="setLegacyTools()">
                        <?php
                        if (
                                !$this->option_legacy_tools['tables'][0] &&
                                !$this->option_legacy_tools['forms'][0] &&
                                !$this->option_legacy_tools['templates'][0] &&
                                !$this->option_legacy_tools['designer'][0] &&
                                !$this->option_legacy_tools['dashboards'][0] &&
                                !$this->option_legacy_tools['charts'][0]
                        ) {
                            ?>
                            <i class="fas fa-toggle-off" style="font-size: 28px;"></i>
                            <?php
                        } else {
                            ?>
                            <i class="fas fa-toggle-on" style="font-size: 28px;"></i>
                            <?php
                        }
                        ?>
                        &nbsp;&nbsp;
                        <span>
                            LEGACY TOOL USAGE
                        </span>
                    </a>
                    <div id="wpda-legacy-tool-settings-panel">
                        <table>
                            <thead>
                                <tr>
                                    <th>LEGACY TOOL USAGE</th>
                                    <th class="items">ACTIVE ITEMS</th>
                                    <th>STATUS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Tables</td>
                                    <td class="items"><?php echo esc_attr( $this->option_legacy_tools['tables'][1] ); ?></td>
                                    <td class="status" data-tool="tables"><?php echo $this->tool_status( $this->option_legacy_tools['tables'] ); ?></td>
                                </tr>
                                <tr>
                                    <td>Forms</td>
                                    <td class="items"><?php echo esc_attr( $this->option_legacy_tools['forms'][1] ); ?></td>
                                    <td class="status" data-tool="forms"><?php echo $this->tool_status( $this->option_legacy_tools['forms'] ); ?></td>
                                </tr>
                                <tr>
                                    <td>Templates</td>
                                    <td class="items"><?php echo esc_attr( $this->option_legacy_tools['templates'][1] ); ?></td>
                                    <td class="status" data-tool="templates"><?php echo $this->tool_status( $this->option_legacy_tools['templates'] ); ?></td>
                                </tr>
                                <tr>
                                    <td>Designer</td>
                                    <td class="items"><?php echo esc_attr( $this->option_legacy_tools['designer'][1] ); ?></td>
                                    <td class="status" data-tool="designer"><?php echo $this->tool_status( $this->option_legacy_tools['designer'] ); ?></td>
                                </tr>
                                <tr>
                                    <td>Dashboards</td>
                                    <td class="items"><?php echo esc_attr( $this->option_legacy_tools['dashboards'][1] ); ?></td>
                                    <td class="status" data-tool="dashboards"><?php echo $this->tool_status( $this->option_legacy_tools['dashboards'] ); ?></td>
                                </tr>
                                <tr>
                                    <td>Charts</td>
                                    <td class="items"><?php echo esc_attr( $this->option_legacy_tools['charts'][1] ); ?></td>
                                    <td class="status" data-tool="charts"><?php echo $this->tool_status( $this->option_legacy_tools['charts'] ); ?></td>
                                </tr>
                            </tbody>
                        </table>

                        <div id="wpda-legacy-tool-settings-submit">
                            <button type="submit" onclick="updateLegacyTools()">UPDATE STATUS</button>
                            <button type="button" onclick="setLegacyTools()">CANCEL</button>
                        </div>

                        <div style="display: none">
                            <form
                                id="wpda-legacy-tool-settings-form-data"
                                action="<?php echo admin_url( 'admin.php' ); ?>?page=wpda_navi"
                                method="POST"
                            >
                                <input
                                    type="text"
                                    name="wpda-legacy-tool-status"
                                    id="wpda-legacy-tool-settings-legacy-tool-data"
                                />
                            </form>
                        </div>
                    </div>
                </div>
            </div>

			<div class="wpda-navi-container-content">
				<div class="wpda-navi-container-content-items">
					<div class="wpda-navi-container-content-item wpda-featured">
						<div class="wpda-navi-container-content-item-title">
							<span class="fa-solid wpda-icon">
								<svg xmlns="http://www.w3.org/2000/svg"
									 height="18px"
									 width="18px"
									 viewBox="4 4 16 16"
									 fill="inherit"
									 class="wpda-icon"
								>
									<path d="M4 8h4V4H4v4zm6 12h4v-4h-4v4zm-6 0h4v-4H4v4zm0-6h4v-4H4v4zm6 0h4v-4h-4v4zm6-10v4h4V4h-4zm-6 4h4V4h-4v4zm6 6h4v-4h-4v4zm0 6h4v-4h-4v4z"></path>
								</svg>
							</span>
							<h3>App Builder</h3>
							<div class="wpda-navi-container-content-item-title-help">
								<a
										href="https://wpdataaccess.com/docs/app-builder/whats-the-app-builder/"
										target="_blank"
										class="wpda_tooltip"
										title="View online documentation"
								>
									<i class="fa-solid fa-question-circle wpda-icon-help"></i>
								</a>
							</div>
						</div>

						<div class="wpda-navi-container-content-item-slogan" style="display: flex; justify-content: space-between; align-items: center;">
                            <span>
                                A data-driven Rapid Application Development tool
                            </span>

                            <a href="https://wpdataaccess.com/docs/app-builder/road-map/" target="_blank" class="roadmap">ROAD MAP</a>
						</div>

						<div class="wpda-navi-container-content-item-content">
							<ul class="wpda-navi-container-content-item-content-grid switch-grid">
								<li>
									<a href="?page=wpda_apps">Start App Builder</a>
								</li>
								<li>
									<a href="?page=wpda_apps&page_iaction=create_new_app">Create a new app</a>
								</li>
							</ul>
						</div>

						<div class="wpda-navi-container-content-item-facts">
							<ul>
								<li>Intuitive Table Builder for creating data tables with ease</li>
								<li>Highly customizable Form Builder for designing data entry forms</li>
								<li>Integrated Theme Builder to personalize app styling</li>
								<li>Interactive Chart Builder for real-time data analysis <span class="wpda-new"><i>NEW</i></span></li>
                                <li>Visualize location data from SQL queries with the Map Builder <span class="wpda-new"><i>NEW</i></span></li>
							</ul>
						</div>
					</div>

					<div class="wpda-navi-container-content-item">
						<div class="wpda-navi-container-content-item-title">
							<span class="fa-solid fa-database wpda-icon"></span>
							<h3>Explorer</h3>
							<div class="wpda-navi-container-content-item-title-help">
								<a
										href="https://wpdataaccess.com/docs/data-explorer/data-explorer-getting-started/"
										target="_blank"
										class="wpda_tooltip"
										title="View online documentation"
								>
									<i class="fa-solid fa-question-circle wpda-icon-help"></i>
								</a>
							</div>
						</div>

						<div class="wpda-navi-container-content-item-slogan">
							Perform data and database related tasks
						</div>

						<div class="wpda-navi-container-content-item-content">
							<ul class="wpda-navi-container-content-item-content-grid">
								<li>
									<a href="?page=wpda">Start Data Explorer</a>
								</li>
								<li>
									<a href="?page=wpda&page_iaction=wpda_import_sql">Import SQL files</a>
								</li>
								<li>
									<a href="?page=wpda&page_iaction=manage_databases">Manage databases</a>
								</li>
								<li>
									<a href="?page=wpda&page_action=wpda_import_csv">Import CSV files</a>
								</li>
							</ul>
						</div>

						<div class="wpda-navi-container-content-item-facts">
							<ul>
                                <li>Schedule unattended exports (new Data Explorer only) <span class="wpda-new"><i>NEW</i></span></li>
                                <li>Manage database connections, databases and table data</li>
								<li>Explore local and remote databases</li>
							</ul>
						</div>
					</div>

					<div class="wpda-navi-container-content-item">
						<div class="wpda-navi-container-content-item-title">
							<span class="fa-solid fa-code wpda-icon"></span>
							<h3>SQL</h3>
							<div class="wpda-navi-container-content-item-title-help">
								<a
										href="https://wpdataaccess.com/docs/sql-query-builder/query-builder-getting-started/"
										target="_blank"
										class="wpda_tooltip"
										title="View online documentation"
								>
									<i class="fa-solid fa-question-circle wpda-icon-help"></i>
								</a>
							</div>
						</div>

						<div class="wpda-navi-container-content-item-slogan">
							Execute any SQL command from your WordPress dashboard
						</div>

						<div class="wpda-navi-container-content-item-content">
							<ul>
								<li>
									<a href="?page=wpda_query_builder">Start Query Builder</a>
								</li>
							</ul>
						</div>

						<div class="wpda-navi-container-content-item-facts">
							<ul>
								<li>Write, store, execute and reuse any SQL command</li>
                                <li>Schedule SQL commands to run at specific intervals <span class="wpda-new"><i>NEW</i></span></li>
								<li>Ask AI Assistant to help writing queries and solve errors <span class="wpda-new"><i>NEW</i></span></li>
                                <li>Store queries privately or globally <span class="wpda-new"><i>NEW</i></span></li>
							</ul>
						</div>
					</div>

					<div class="wpda-navi-container-content-item wpda-featured"
						 style="grid-template-rows: auto 1fr"
					>
						<div class="wpda-navi-container-content-item-title">
							<span class="fa-solid fa-comments wpda-icon"></span>
							<h3>What's new?</h3>
							<div class="wpda-navi-container-content-item-title-help">
								<a
										href="https://wordpress.org/plugins/wp-data-access/#developers"
										target="_blank"
										class="wpda_tooltip"
										title="View full changelog"
								>
									<i class="fa-solid fa-question-circle wpda-icon-help"></i>
								</a>
							</div>
						</div>

						<div class="wpda-navi-container-content-item-facts whats-new">
							<ul>
                                <li>Schedule unattended exports (new Data Explorer only)</li>
                                <li>
                                    <a href="https://wpdataaccess.com/docs/sql-query-builder/query-builder-getting-started/" target="_blank" class="whatsnew">
                                    New SQL Query Builder with AI Assistant and scheduled queries
                                    </a>
                                </li>
                                <li>
                                    Edit date and time fields in MySQL date format <i class="fas fa-chevron-right"></i> <span class="wpda-new">Form Builder</span>
                                </li>
                                <li>
                                    Visualize location data on
                                    <a href="https://wpdataaccess.com/docs/map-builder/map-builder-getting-started/" target="_blank" class="whatsnew">
                                        Maps
                                    </a>
                                    using SQL queries
                                </li>
                                <li>
                                    <a href="https://wpdataaccess.com/docs/hooks/getting-started-with-hooks/" target="_blank" class="whatsnew">
                                        Hooks for Tables and Forms
                                    </a>
                                </li>
                                <li>
                                    New built-ins
                                    <a href="https://wpdataaccess.com/docs/built-ins/built-ins-overview/" target="_blank" class="whatsnew">
                                        log, alert, confirm and snackbar
                                    </a>
                                    available in all hooks
                                </li>
                                <li>
                                    Lightning-Fast Filtering with
                                    <a href="https://wpdataaccess.com/docs/table-builder-performance/server-client-side-processing/" target="_blank" class="whatsnew">
                                        Client-Side Processing
                                    </a>
                                </li>
							</ul>
						</div>
					</div>
				</div>
			</div>
			<?php
		}

		private function hot_topics() {
			?>
			<div class="wpda-navi-container-content">
				<div class="wpda-navi-container-content-item wpda-hot-topics">
					<h2>
						Frequently Asked Questions
					</h2>

					<div class="wpda-navi-container-content-item-facts">
						<button
							onClick="window.open('https://wpdataaccess.com/docs/app-builder/whats-the-app-builder/', '_blank')"
						>
							<span class="wpda-hot-title">
								App Builder
							</span>
							<span class="wpda-hot-topic">
								What is the App Builder?
							</span>
						</button>

						<button
							onClick="window.open('https://wpdataaccess.com/docs/app-builder/app-builder-relationships/', '_blank')"
						>
							<span class="wpda-hot-title">
								Master-Detail Relationships
							</span>
							<span class="wpda-hot-topic">
								How do I add a master-detail relationship to my app?
							</span>
						</button>

						<button
							onClick="window.open('https://wpdataaccess.com/docs/app-builder/computed-fields/', '_blank')"
						>
							<span class="wpda-hot-title">
								Computed Fields
							</span>
							<span class="wpda-hot-topic">
								How do I use a computed field in my app?
							</span>
						</button>

						<button
							onClick="window.open('https://wpdataaccess.com/docs/app-builder/app-lookups/', '_blank')"
						>
							<span class="wpda-hot-title">
								Lookups
							</span>
							<span class="wpda-hot-topic">
								How do I add a lookup to my app?
							</span>
						</button>

						<button
							onClick="window.open('https://wpdataaccess.com/docs/table-builder-filters/column-filters/', '_blank')"
						>
							<span class="wpda-hot-title">
								Column Filters
							</span>
							<span class="wpda-hot-topic">
								How do I enable column filters in my app?
							</span>
						</button>

						<button
							onClick="window.open('https://wpdataaccess.com/documentation/', '_blank')"
						>
							<span class="wpda-hot-title">
								Documentation
							</span>
							<span class="wpda-hot-topic">
								Where can I find the online documentation?
							</span>
						</button>

						<button>
							<span class="wpda-hot-title">
								Downloadable Demo Apps
							</span>
							<span class="wpda-hot-topic" style="display: inline-grid; grid-template-columns: auto auto; gap: 20px;">
                                <a href="https://wpdataaccess.com/docs/app-demos/app-student-administration-system/" target="_blank">
                                    <i class="fa-solid fa-up-right-from-square"></i>
                                    Student Administration System
                                </a>
                                <a href="https://wpdataaccess.com/docs/app-demos/app-classic-models/" target="_blank">
                                    <i class="fa-solid fa-up-right-from-square"></i>
                                    Classic Models
                                </a>
							</span>
						</button>

						<button
							onClick="window.open('https://wpdataaccess.com/docs/remote-databases/mysql-mariadb/', '_blank')"
						>
							<span class="wpda-hot-title">
								Remote connections
							</span>
							<span class="wpda-hot-topic">
								How can I establish a remote connection?
							</span>
						</button>

						<button
							onClick="window.open('https://wpdataaccess.com/docs/remote-connection-wizard/start-here/', '_blank')"
						>
							<span class="wpda-hot-title">
								Premium Data Services
							</span>
							<span class="wpda-hot-topic">
								How can I use Premium Data Services for remote connections?
							</span>
						</button>
					</div>
				</div>
			</div>
			<?php
		}

	}

}