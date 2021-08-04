fname = '../json/speedOverPosition_v1.json'; 
fid = fopen(fname); 
raw = fread(fid,inf); 
str = char(raw'); 
fclose(fid); 
val = jsondecode(str);

speedOverPosition_x = val(:,1);

speedOverPosition_y = val(:,2);


p = plot(speedOverPosition_x,speedOverPosition_y);
p.LineWidth = 2;
fontSize = 18;
title("Geschwindigkeit in Abhähngigkeit der Position", 'FontSize', fontSize);
xlabel("Strecke [m]", 'FontSize', fontSize);
ylabel("Geschwindigkeit [km/h]", 'FontSize', fontSize);
x0=10;
y0=10;
width=1100;
height=600;
axis([-10 500 0 55])
set(gcf,'position',[x0,y0,width,height]);
set(gca, 'FontSize', 14);
t = gca;
exportgraphics(t,'SpeedOverPosition.jpg','Resolution',300);


