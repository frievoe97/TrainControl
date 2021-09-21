%% Load cumulative sections

fname = '../json/VMaxOverCumulativeSections.json'; 
fid = fopen(fname); 
raw = fread(fid,inf); 
str = char(raw'); 
fclose(fid); 
vmaxOverPosition = jsondecode(str);
vmaxOverPosition_Position = vmaxOverPosition(:,1);
vmaxOverPosition_v_max = vmaxOverPosition(:,2);

%% Load modified cumulative sections

fname = '../json/VMaxOverCumulativeSectionsMod.json'; 
fid = fopen(fname); 
raw = fread(fid,inf); 
str = char(raw'); 
fclose(fid); 
vmaxOverPosition_mod = jsondecode(str);
vmaxOverPosition_Position_mod = vmaxOverPosition_mod(:,1);
vmaxOverPosition_v_max_mod = vmaxOverPosition_mod(:,2);

%% Load speed over position

fname = '../json/speedOverPosition.json'; 
fid = fopen(fname); 
raw = fread(fid,inf); 
str = char(raw'); 
fclose(fid); 
val = jsondecode(str);

speedOverPosition_x_v1 = val(:,1);
speedOverPosition_y_v1 = val(:,2);

%% Load speed over position (all iterationsteps)

fname_it = '../json/speedOverPosition_prevIterations.json'; 
fid_it = fopen(fname_it); 
raw_it = fread(fid_it,inf); 
str_it = char(raw_it'); 
fclose(fid_it); 
val_it = jsondecode(str_it);

%% Plot

hold on
figure(1)

% Plot infrastructuresections

p = line([0 0], [0 vmaxOverPosition_v_max(1)],'Linestyle','-.','LineWidth',2,'color','black','DisplayName',['Infra-Abschnitte']); 
line([0 vmaxOverPosition_Position(1)], [vmaxOverPosition_v_max(1) vmaxOverPosition_v_max(1)],'Linestyle','-.','LineWidth',2,'color','black', 'HandleVisibility','off');

for i = 1:size(vmaxOverPosition_Position) - 1
   line([vmaxOverPosition_Position(i) vmaxOverPosition_Position(i + 1)], [vmaxOverPosition_v_max(i + 1) vmaxOverPosition_v_max(i + 1)],'Linestyle','-.','LineWidth',2,'color','black', 'HandleVisibility','off');
   line([vmaxOverPosition_Position(i) vmaxOverPosition_Position(i)], [0 vmaxOverPosition_v_max(i + 1)],'Linestyle','-.','LineWidth',2,'color','black', 'HandleVisibility','off'); 
   line([vmaxOverPosition_Position(i) vmaxOverPosition_Position(i)], [0 vmaxOverPosition_v_max(i)],'Linestyle','-.','LineWidth',2,'color','black', 'HandleVisibility','off'); 
   line([vmaxOverPosition_Position(i + 1) vmaxOverPosition_Position(i + 1)], [0 vmaxOverPosition_v_max(i + 1)],'Linestyle','-.','LineWidth',2,'color','black', 'HandleVisibility','off'); 
end

% Plot modified iterationsteps (incl. trainlength)
line([0 0], [0 vmaxOverPosition_v_max_mod(1)],'Linestyle','-.','LineWidth',2,'color','red', 'HandleVisibility','off'); 
line([0 vmaxOverPosition_Position_mod(1)], [vmaxOverPosition_v_max_mod(1) vmaxOverPosition_v_max_mod(1)],'Linestyle','-.','LineWidth',2,'color','red','DisplayName',['Infra-Abschnitte' newline 'inkl. Zuglänge']);

for i = 1:size(vmaxOverPosition_Position_mod) - 1
   line([vmaxOverPosition_Position_mod(i) vmaxOverPosition_Position_mod(i + 1)], [vmaxOverPosition_v_max_mod(i + 1) vmaxOverPosition_v_max_mod(i + 1)],'Linestyle','-.','LineWidth',2,'color','red', 'HandleVisibility','off');
   line([vmaxOverPosition_Position_mod(i) vmaxOverPosition_Position_mod(i)], [0 vmaxOverPosition_v_max_mod(i + 1)],'Linestyle','-.','LineWidth',2,'color','red', 'HandleVisibility','off'); 
   line([vmaxOverPosition_Position_mod(i) vmaxOverPosition_Position_mod(i)], [0 vmaxOverPosition_v_max_mod(i)],'Linestyle','-.','LineWidth',2,'color','red', 'HandleVisibility','off'); 
   line([vmaxOverPosition_Position_mod(i + 1) vmaxOverPosition_Position_mod(i + 1)], [0 vmaxOverPosition_v_max_mod(i + 1)],'Linestyle','-.','LineWidth',2,'color','red', 'HandleVisibility','off'); 
end

% Plot all iterationsteps
for i = 1:length(val_it)
    plot(val_it{i}(:,1),val_it{i}(:,2),'.','markersize',8,'Color', [0.6 0.6 0.6],'DisplayName',legend_name);
    
end

% PLot speedcurve

plot(speedOverPosition_x_v1,speedOverPosition_y_v1,'LineWidth',4,'Color', [0.25 0.80 0.54],'DisplayName','Fahrtverlauf');

%% Fillobjects

%fill([0, 0, 1090, 1090, 0], [0, 200, 200, 0, 0], 'b','facealpha',.2,'LineStyle','none');

%% Adding text

%text(20,17,'1','Interpreter','latex','fontsize', 40);

%% Format plot

p.LineWidth = 2;
box off
fontSize = 18;
xlabel("Strecke [m]", 'FontSize', fontSize);
ylabel("Geschwindigkeit [km/h]", 'FontSize', fontSize);
x0=10;
y0=10;
width=1100;
height=600;
axis([-80 max(vmaxOverPosition_Position)+80 0 max(vmaxOverPosition_v_max)+10]);
axis([-20 max(vmaxOverPosition_Position)+20 0 max(vmaxOverPosition_v_max)+5]);
set(gcf,'position',[x0,y0,width,height]);
set(gcf,'PaperPositionMode','auto');
set(gca, 'FontSize', 18);
set(gca, 'Linewidth', 2);

t = gca;
exportgraphics(t,'SpeedOverPosition.pdf','ContentType','vector');
hold off