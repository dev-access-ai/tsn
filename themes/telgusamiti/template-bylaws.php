<?php
/**
 * Template Name: By Laws
 * Description: Pages for TSN By-Laws with dynamic accordion layout from Editor Content
 *
 * @package TeluguSamiti
 */
get_header();
?>

<div id="inner-banner">
  <div class="container">
    <div class="banner-content">
      <div class="section-title">
        <h3><?php the_title(); ?></h3>
      </div>
      <?php telugusmiti_breadcrumb(); ?>
    </div>
  </div>
</div>

<main id="main" class="site-main">
    <section class="main-content-section">
        <div class="container">
            <div class="section-title" style="margin-bottom: 30px;">
                <!-- We can allow the user to set this title in the editor content, 
                     or keep it hardcoded if they only edit the "Articles" below. 
                     For full flexibility, I'll remove the hardcoded Main Title 
                     so the page title or content H1/H2 serves that purpose.
                     However, the user sent specific text. I will provide a container
                     where the content lives. -->
                <?php if(have_posts()): while(have_posts()): the_post(); ?>
                   <!-- The Content from WordPress Editor will be rendered here -->
                   <div class="tsn-bylaws-content-source" style="display: none;">
                       <?php the_content(); ?>
                   </div>
                <?php endwhile; endif; ?>
            </div>

            <!-- This is where the JS will build the accordion -->
            <div id="tsn-accordion-container" class="tsn-accordion"></div>

            <style>
                /* Accordion Styles - Refined for Readability */
                .tsn-accordion {
                    border: 1px solid #e0e0e0;
                    border-radius: 8px;
                    overflow: hidden;
                    margin-bottom: 20px;
                    box-shadow: 0 4px 10px rgba(0,0,0,0.03); /* Softer shadow */
                }
                .tsn-accordion-item {
                    border-bottom: 1px solid #e0e0e0;
                }
                .tsn-accordion-item:last-child {
                    border-bottom: none;
                }
                .tsn-accordion-header {
                    background-color: #fcfcfc; /* Lighter background */
                    padding: 25px 30px; /* More breathing room */
                    cursor: pointer;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    transition: all 0.3s ease;
                }
                .tsn-accordion-header:hover {
                    background-color: #f4f7f9;
                }
                .tsn-accordion-header h5 {
                    margin: 0;
                    color: #2c3e50; /* Darker, cleaner color */
                    font-weight: 600;
                    font-size: 19px; /* Slightly larger */
                    letter-spacing: 0.5px;
                    line-height: 1.4;
                }
                .tsn-accordion-icon {
                    font-size: 18px;
                    color: #95a5a6;
                    transition: transform 0.3s ease;
                }
                .tsn-accordion-content {
                    display: none;
                    padding: 30px 40px; /* Generous padding */
                    background-color: #fff;
                    line-height: 1.8; /* improved line height */
                    color: #4a4a4a; /* softer text color */
                    font-size: 16px; /* Base font size */
                }
                /* Styling for user content inside accordion */
                .tsn-accordion-content h6, 
                .tsn-accordion-content h4, 
                .tsn-accordion-content strong {
                    font-size: 17px;
                    font-weight: 600;
                    color: #2c3e50;
                    margin-top: 25px; /* Separate sections */
                    margin-bottom: 12px;
                    display: block;
                }
                
                /* List styling */
                .tsn-accordion-content ul, .tsn-accordion-content ol {
                    margin-left: 20px;
                    margin-bottom: 20px;
                    padding-left: 15px;
                }
                .tsn-accordion-content li {
                    margin-bottom: 8px; /* Space between list items */
                    padding-left: 5px;
                }
                
                /* Paragraph spacing */
                .tsn-accordion-content p {
                    margin-bottom: 20px;
                }
                
                /* Active State */
                .tsn-accordion-item.active .tsn-accordion-header {
                    background-color: #f0f4f8; /* Subtle highlight */
                    border-left: 4px solid #3498db; /* Accent on active */
                    padding-left: 26px; /* Adjust for border */
                }
                .tsn-accordion-item.active .tsn-accordion-header h5 {
                     color: #2980b9;
                }
                .tsn-accordion-item.active .tsn-accordion-icon {
                    transform: rotate(180deg);
                    color: #3498db;
                }
                .tsn-accordion-item.active .tsn-accordion-content {
                    display: block;
                    animation: fadeIn 0.4s ease;
                }
                @keyframes fadeIn {
                    from { opacity: 0; transform: translateY(-5px); }
                    to { opacity: 1; transform: translateY(0); }
                }
                
                /* Responsive adjustments */
                @media (max-width: 768px) {
                    .tsn-accordion-header { padding: 20px; }
                    .tsn-accordion-content { padding: 20px; }
                    .tsn-accordion-header h5 { font-size: 17px; }
                }
            </style>
            
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    var sourceContainer = document.querySelector('.tsn-bylaws-content-source');
                    var targetContainer = document.getElementById('tsn-accordion-container');
                    
                    if (!sourceContainer || !targetContainer) return;
                    
                    // We need to parse the source content.
                    // Expectation: 
                    // <h5>ARTICLE I...</h5> -> New Accordion Item Header
                    // Everything else -> Accordion Content
                    
                    // Un-hide source momentarily to access children or just use innerHTML?
                    // Better to work with DOM nodes.
                    sourceContainer.style.display = 'block'; // Allow browser to parse children
                    var children = Array.from(sourceContainer.children);
                    sourceContainer.style.display = 'none'; // Hide again
                    
                    var currentAccordionItem = null;
                    var currentContentDiv = null;
                    
                    // Helper to create new item
                    function createAccordionItem(titleText) {
                        var item = document.createElement('div');
                        item.className = 'tsn-accordion-item';
                        
                        var header = document.createElement('div');
                        header.className = 'tsn-accordion-header';
                        
                        var h5 = document.createElement('h5');
                        h5.textContent = titleText; // Or innerHTML if user puts formatting in title? textContent is safer/cleaner.
                        
                        var icon = document.createElement('span');
                        icon.className = 'tsn-accordion-icon';
                        icon.innerHTML = '&#9660;';
                        
                        header.appendChild(h5);
                        header.appendChild(icon);
                        
                        // Click Event
                        header.addEventListener('click', function() {
                            item.classList.toggle('active');
                        });
                        
                        var content = document.createElement('div');
                        content.className = 'tsn-accordion-content';
                        
                        item.appendChild(header);
                        item.appendChild(content);
                        
                        return { item: item, content: content };
                    }
                    
                    // Initial check: if first element is NOT an H5, we might have intro text.
                    // We can either leave it outside the accordion or put it in a "General" section.
                    // Let's dump it before the accordion if it exists.
                    var hasCreatedFirstItem = false;
                    
                    children.forEach(function(child) {
                        // Check if tag is H5 (The "Article" Header)
                        if (child.tagName === 'H5') {
                            var created = createAccordionItem(child.textContent);
                            currentAccordionItem = created.item;
                            currentContentDiv = created.content;
                            targetContainer.appendChild(currentAccordionItem);
                            hasCreatedFirstItem = true;
                        } else {
                            if (hasCreatedFirstItem && currentContentDiv) {
                                // Add to current accordion
                                currentContentDiv.appendChild(child.cloneNode(true));
                            } else {
                                // Content BEFORE the first Article (Introductory text)
                                // Insert it BEFORE the accordion container
                                var introDiv = document.createElement('div');
                                introDiv.className = 'tsn-bylaws-intro';
                                introDiv.style.marginBottom = '20px';
                                introDiv.appendChild(child.cloneNode(true));
                                targetContainer.parentNode.insertBefore(introDiv, targetContainer);
                            }
                        }
                    });
                    
                });
            </script>

        </div>
    </section>
</main>

<?php get_footer(); ?>
